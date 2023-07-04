<?php

namespace table;

use Nenge\DB;
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
    public function merge_file($file, $data = array())
    {
        $myapp = \Nenge\APP::app();
        $uid = !empty($data['uid']) ? $data['uid'] : 0;
        $filearr = explode('.',$file['name']);
        $fileext = array_pop($filearr);
        $filename = $uid.date('_Y-m-d_').$file['name'].'.attach';
        $path = $myapp->data['path']['tmp'].$filename;
        $filesize = $data['filesize'];
        $nowpos = $data['nowpos'];
        $bsize = filesize($path);
        $endsize = $nowpos+$file['size'];
        if($bsize<$filesize){
            if(!$nowpos||$bsize<=$nowpos){
                $fp = fopen($path,'ab');
                $content = file_get_contents($file['tmp_name']);
                $result = fwrite($fp,$content);
                fclose($fp);
                if($filesize>$endsize){
                    return array('result'=>$result,'orgsize'=>$bsize,'endsize'=>$endsize,'pos'=>$nowpos);
                }
            }else{
                return array('result'=>'pass','orgsize'=>$bsize,'endsize'=>$bsize,'pos'=>$nowpos);
            }
        }else{
            return array('result'=>'success');
        }
        if($filesize==$endsize){
            $newfile = date('Y-m') . '\\' . date('d-' . $uid . 'His') . substr(md5($file['name']), 0, 5) . '.attach';
            $filepath = $myapp->data['path']['attach'].$newfile;
            $root = dirname($filepath);
            if (!is_dir($root)) {
                \Nenge\APP::app()->mkdir($root);
            }
            if(rename($path,$filepath)){
                return array('orgfilename' => $file['name'], 'filename' => str_replace('\\', '/', $newfile), 'filesize' => $filesize, 'filetype' =>$fileext, 'width' => 0, 'height' =>0);
            }
        }
        return array('result'=>'error');
    }
    public function save_attach($file, $data = array())
    {
        $uid = !empty($data['uid']) ? $data['uid'] : 0;
        if (!empty($file)) {
            if (is_array($file['tmp_name'])) {
                foreach ($file['tmp_name'] as $k => $v) {
                    if ($file['error'][$k] == 0 && is_uploaded_file($v)) {
                        $result[] = $this->move_file($file['tmp_name'][$k], $file['name'][$k], $file['type'][$k], $file['size'][$k], $uid);
                    }
                }
            } else if ($file['error'] == 0 && is_uploaded_file($file['tmp_name'])) {
                if($file['type']=='application/x-path'){
                    $result[] =  $this->merge_file($file,$data);
                    unset($data['nowpos']);
                }else{
                    $result[] = $this->move_file($file['tmp_name'], $file['name'], $file['type'], $file['size'], $uid);
                }
            }
        }
        if (!empty($result)) {
            if(empty($result[0]['orgfilename'])) return $result[0];
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
                $apath = \Nenge\APP::app()->data['site']['attach'];
                foreach ($inserdata as $k => $v) {
                    $datas[] = array_values($v);
                    $file[] = $v['isimage'] ? array($apath . $v['filename'], $v['orgfilename']) : '';
                }
                $data = $this->insert($datas);
                foreach ($data as $k => $v) {
                    if (empty($file[$k])) {
                        $result[$v['lastid']] = '';
                    } else {
                        $result[$v['lastid']] = $file[$k];
                    }
                }
                return $result;
            }
        }
    }
    public function move_file($tmp, $name, $type, $size, $uid)
    {
        $file = date('Y-m') . '\\' . date('d-' . $uid . 'His') . substr(md5($name), 0, 5) . '.';
        $width = 0;
        $height = 0;
        $mime = $this->getMime($tmp);
        if (stripos($mime, 'image/') !== false) {
            $imginfo = getimagesize($tmp);
            $width = $imginfo[0];
            $height = $imginfo[1];
            $file .= str_replace('image/', '', $mime);
            $mime = 'image';
        } else if (stripos($mime, 'zip') !== false) {
            $file .= 'zip';
            $mime = 'zip';
        } else if (stripos($mime, '7z') !== false) {
            $file .= '7z';
            $mime = '7z';
        } else if (stripos($mime, 'rar') !== false) {
            $file .= 'rar';
            $mime = 'rar';
        } else {
            $file .= 'attach';
            $mime = 'other';
        }
        $path = \Nenge\APP::app()->data['path']['attach'] . $file;
        $root = dirname($path);
        if (!is_dir($root)) {
            \Nenge\APP::app()->mkdir($root);
        }
        if (is_uploaded_file($tmp) && move_uploaded_file($tmp, $path)) {
            return array('orgfilename' => $name, 'filename' => str_replace('\\', '/', $file), 'filesize' => $size, 'filetype' => $mime, 'width' => $width, 'height' => $height);
        }
    }
    public function getMime($link)
    {
        $mimelist = array(
            'application/x-zip-compressed'=>'/^377ABCAF271C/',
            'application/x-rar-compressed'=>'/^52617221/',
            'application/x-7z-compressed'=>'/^504B0304/',
            'image/png'=>'/^89504E470D0A1A0A/',
            'image/gif'=>'/^47494638(3761|3961)/',
            'image/jpg'=>'/^FFD8FF/',
            'image/webp'=>'/^52494646\w{8}57454250/',
            'image/bmp'=>'/^424D\w{4}0{8}/',
        );
        $fp = fopen($link,'rb');
        $buf = bin2hex(fread($fp,16));
        fclose($fp);
        foreach($mimelist as $k=>$v){
            if(preg_match($v,$buf)) return $k;
        }
        return 'application/octet-stream';
    }
}
