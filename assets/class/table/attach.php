<?php

namespace table;

use Nenge\DB;
use Nenge\APP;
use PDO;

class table_attach extends base
{
    public array $list = array();
    function __construct()
    {
        $this->table = 'attach';
        $this->indexkey = 'aid';
    }
    public function fetch_by_aids($aids)
    {
        if (empty($aids)) return array();
        $result = $this->all(array('aid' => $aids));
        if (!empty($result)) $this->list += $result;
        else $result = array();
        return $result;
    }
    public function fetch_by_pids($aids)
    {
        if (empty($aids)) return array();
        $result = $this->all(array('pid' => $aids));
        if (!empty($result)) $this->list += $result;
        else $result = array();
        return $result;
    }
    public function aids($aids)
    {
        if (is_numeric($aids)) {
            if (isset($this->list[$aids])) return $this->list[$aids];
            else {
                $result = $this->fetch_by_aids($aids);
                if (!empty($result[$aids])) return $result[$aids];
            }
            return $result;
        } elseif (is_string($aids)) return array();
        $aids = array_unique($aids);
        $newaids = [];
        $result = [];
        foreach ($aids as $k => $v) {
            if (empty($v)) continue;
            $v = (int) $v;
            if (empty($this->list[$v])) {
                $newaids[] = $v;
            } else {
                $result[$v] = $this->list[$v];
            }
        }
        return $result + $this->fetch_by_aids($newaids);
    }
    /**
     * @param FILES $file 上传的文件
     * @param array $data 保存参数,如果含有aid将会变成更新操作
     * @return array 返回信息
     */

    public function save_attach($file, $data = array())
    {
        if(!empty($data['aid'])){
            $olddata = $this->fetch(array('aid'=>$data['aid']));
            $oldfile = $olddata['filename'];
            $uid = $olddata['uid'];
        }else{
            $uid = !empty($data['uid']) ? $data['uid'] : 0;
        }
        if (!empty($file)) {
            if (is_array($file['tmp_name'])) {
                foreach ($file['tmp_name'] as $k => $v) {
                    if ($file['error'][$k] == 0 && is_uploaded_file($v)) {
                        $result[] = $this->move_file_to_attach($file['tmp_name'][$k], $file['name'][$k], $file['type'][$k], $file['size'][$k], $uid);
                    }
                    if(!empty($data['aid']))break;
                }
            } else if ($file['error'] == 0 && is_uploaded_file($file['tmp_name'])) {
                if ($file['type'] == 'application/x-path') {
                    $result[] =  $this->merge_file_to_attach($file, $uid);
                } else {
                    $result[] = $this->move_file_to_attach($file['tmp_name'], $file['name'], $file['type'], $file['size'], $uid);
                }
            }
        }
        $attach_path = APP::app()->data['site']['attach'];
        if (!empty($result)) {
            if (empty($result[0]['orgfilename'])) return $result[0];
            if(!empty($oldfile))unlink($attach_path.$oldfile);
            if (!empty($data['aid'])) {
                $newdata = array_merge($data, $result[0]);
                $this->update($newdata,array('aid'=>$data['aid']));
                $result = array();
                if (empty($data['isimage'])) {
                    $result['attachs'][] = $data['aid'];
                } else {
                    $result['images'][$newdata['aid']] = array( $attach_path. $newdata['filename'], $newdata['orgfilename']);
                }
                return $result;
            }
            $inserdata = array();
            foreach ($result as $k => $v) {
                if (!empty($v)) {
                    $tmp = array();
                    $tmp = array_merge($tmp, $data, $v);
                    $tmp['create_date'] = time();
                    $tmp['isimage'] = $v['height'] > 0 ? 1 : 0;
                    $inserdata[] = $tmp;
                }
            }
            //return $inserdata;
            if (!empty($inserdata)) {
                $datas[] = array_keys($inserdata[0]);
                $file = array();
                $result = array();
                foreach ($inserdata as $k => $v) {
                    if (empty($v['orgfilename'])) {
                        $result['error'][] = $v;
                        continue;
                    }
                    $datas[] = array_values($v);
                    $file[] = $v['isimage'] ? array($attach_path . $v['filename'], $v['orgfilename']) : '';
                }
                $data = $this->insert($datas);
                foreach ($data as $k => $v) {
                    if (empty($file[$k])) {
                        $result['attachs'][] = $v['lastid'];
                    } else {
                        $result['images'][$v['lastid']] = $file[$k];
                    }
                }
                return $result;
            }
        }
    }
    /**
     * @param filename $tmp 上传的文件片段,mime:application/x-path
     * @param int $uid 用户ID
     * @var string $filemd5 文件MD5
     * @var int $filepos 文件片段起始位置
     * @var int $pathsize 文件片段结束位置
     * @var int $filesize 文件总大小
     * 大文件合拼上传
     * @return array array(orgfilename,filename,filesize,filetype,width,height)
     * PHP接口
     * $result = Nenge\DB::t('attach')->save_attach($_FILES['attchfile'],array('uid'=>''));
     * JavaScript 实例
     *z = await Nenge.FetchItem({url: 'test.zip?' + T.time,type: 'blob'});
     *z = files[0] upload file;
     *var filesize = z.size;
     *var filemd5 = await T.CF('md5file',z);
     *for(var i=0;i<z.size;){
     *    let k = i+512*1024;
     *    if(k>z.size)k=z.size;
     *    await T.ajax({
     *        url: location.href,
     *        post:I.post({
     *            attchfile: new File([z.slice(i,k)],z.name,{type:'application/x-path'}),
     *            filesize:z.size,
     *            filepos:i,
     *            filemd5,
     *            pathsize:k
     *        }),
     *        success(text, headers) {
     *        console.log(text,headers);
     *        }
     *    });
     *    i=k;
     *}
     */
    public function merge_file_to_attach($tmp, $uid)
    {
        //逻辑思路按文件顺序合拼
        if (empty($_POST['filemd5']) || !isset($_POST['filepos']) || empty($_POST['filesize'])) return array('result' => '参数不足{filemd5,filepos,filesize}');
        $myapp = APP::app();
        #$filesize = intval($_POST['filesize']);
        $filemd5 = basename($_POST['filemd5']);
        $filepos = intval($_POST['filepos']);
        $filesize = intval($_POST['filesize']);
        $filename = $uid . '_' . $filemd5 . '.attach';
        $path = $myapp->data['path']['tmp'] . $filename;
        $nowsize = @filesize($path)?:0;
        if ($filepos == $nowsize) {
            //$content = file_get_contents($tmp['tmp_name']);
            //$tmpdata = @file_get_contents($tmp['tmp_name']);
            $fp2 = @fopen($tmp['tmp_name'],'rb');
            if(empty($fp2)){
                return array('result' => 'error','pos'=>$filepos,'size'=>$filesize,'now'=>$nowsize);}
            $fp = fopen($path, 'ab');
            fwrite($fp,fread($fp2,filesize($tmp['tmp_name'])));
            fclose($fp2);
            fclose($fp);
        }else{
            return array('result' => 'error','pos'=>$filepos,'size'=>$filesize,'now'=>$nowsize);
        }
        #$endsize = $nowpos + $tmp['size'];
        #$nowpos =  intval($_POST['nowpos']);
        if (empty($_POST['pathsize'])) {
            $pathsize = @filesize($path)?:0;
        } else {
            $pathsize = intval($_POST['pathsize']);
        }
        if ($pathsize == $filesize) {
            if (md5_file($path) == $filemd5) {
                if ($mime = $this->getMime($path)) {
                    $newfile = $this->get_attach_create_name($path, $uid) . $mime;
                    $filepath = $myapp->data['path']['attach'] . $newfile;
                    $filesize = filesize($path);
                    $root = dirname($filepath);
                    if (!is_dir($root)) {
                        $myapp->mkdir($root);
                    }
                    if (rename($path, $filepath)) {
                        return array('orgfilename' => $tmp['name'], 'filename' => str_replace('\\', '/', $newfile), 'filesize' => $filesize, 'filetype' => $mime, 'width' => 0, 'height' => 0);
                    }
                }
            }
            //unlink($path);
            return array('result' => 'error');
        } else {
            return array('result' => 'success');
        }
    }
    public function move_file_to_attach($tmp, $name, $type, $size, $uid)
    {
        $file = $this->get_attach_create_name($tmp, $uid);
        $myapp = APP::app();
        $width = 0;
        $height = 0;
        #print_r(getimagesize($tmp));exit;
        if ($imginfo = getimagesize($tmp)) {
            $width = $imginfo[0];
            $height = $imginfo[1];
            if (isset($this->imgtype[$imginfo[2]])) {
                $file .= $this->imgtype[$imginfo[2]];
            } else {
                $mimeName = str_replace('image/', '', $imginfo['mime']);
                if ($mimeName == 'jpeg') {
                    $file .= "jpg";
                } else {
                    $file .= $mimeName;
                }
            }
            $mime = 'image';
        } else if ($mime = $this->getMime($tmp)) {
            $file .= $mime;
            if ($mime == 'attach') $mime = 'other';
        } else {
            return array('result' => '附件只支持压缩文件或者图片文件!', 'errorfie' => $name);
        }
        $path = $myapp->data['path']['attach'] . $file;
        $root = dirname($path);
        #echo $this->getMime($tmp);
        #echo str_replace('image/', '',$type);
        #echo $file;exit;
        if (!is_dir($root)) {
            $myapp->mkdir($root);
        }
        if (is_uploaded_file($tmp) && move_uploaded_file($tmp, $path)) {
            return array('orgfilename' => $name, 'filename' => str_replace('\\', '/', $file), 'filesize' => $size, 'filetype' => $mime, 'width' => $width, 'height' => $height);
        }
    }
    public function get_attach_create_name($tmp, $uid)
    {
        return date('Y\\\m\\\d') . '\\' . date('His') . '_' . str_pad($uid, 3, '0', STR_PAD_LEFT) . '_' . md5_file($tmp) . '.';
    }
    public function getMime($link)
    {
        $fp = fopen($link, 'rb');
        $buf = bin2hex(fread($fp, 16));
        fclose($fp);
        foreach ($this->mimelist as $k => $v) {
            if (preg_match($v, $buf)) return $k;
        }
        #echo $buf;
        return 'attach';
    }
    public $mimelist = array(
        'zip' => "/^504b0304/",
        'rar' => "/^52617221/",
        '7z' => "/^377abcaf271c/",
        //'png' => "/^89504e470d0a1a0a/",
        //'gif' => "/^47494638(3761|3961)/",
        //'jpg' => "/^ffd8ffe000104a464946/",
        //'webp' => "/^52494646w{8}57454250/",
        //'bmp' => "/^424dw{4}0{8}/",
    );
    public $imgtype = array(
        IMAGETYPE_GIF => 'gif',
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_SWF => 'swf',
        IMAGETYPE_PSD => 'psd',
        IMAGETYPE_BMP => 'bmp',
        IMAGETYPE_TIFF_II => 'tiff',
        IMAGETYPE_TIFF_MM => 'tiff',
        IMAGETYPE_JPC => 'jpc',
        IMAGETYPE_JP2 => 'jp2',
        IMAGETYPE_JPX => 'jpx',
        IMAGETYPE_JB2 => 'jb2',
        IMAGETYPE_SWC => 'swc',
        IMAGETYPE_IFF => 'iff',
        IMAGETYPE_WBMP => 'wbmp',
        IMAGETYPE_XBM => 'xbm',
        IMAGETYPE_ICO => 'ico',
        IMAGETYPE_WEBP => 'webp'
    );
}
