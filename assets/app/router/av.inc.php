<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 板块列表 板块帖子
 */
defined('WEBROOT') or die('return to <a href="">Home</a>');
$av_root = $myapp->data['path']['upload'].'cmvod\\';
$av_root2 = $myapp->data['path']['upload'].'cmvodurl\\';
$error = '{"error":"Operation timed out after 60015 milliseconds with 0 bytes received"}';
if($myapp['router'][1]=='check'):
    //SELECT * FROM `bbs_cmvod` WHERE `url` LIKE '%91av.cyou%' ORDER BY `id` DESC
    
    $check = $myapp->t('cmvod')->fetch(array('%:url'=>'%91av.cyou%'));
    if(!empty($check)){
        $m3u8_data = $myapp->getRequest($check['url']);
        if(!empty($m3u8_data)):
            $m3u8_data = preg_replace('/kkkkkkkk/','7.cdata.cc',$m3u8_data);
            $m3u8URL = $check['id'].'.m3u8';
            file_put_contents($av_root2.$m3u8URL,$m3u8_data);
            $myapp->t('cmvod')->update(array('url'=>$m3u8URL),array('id'=>$check['id']));
            echo '<h1>OK</h1><script>setTimeout(()=>{location.reload()},1000);</script>';
            echo $myapp->debug();
        endif;
    }
    print_r($check);
    exit;
endif;
if($myapp['router'][1]=='redown'):
    //SELECT * FROM `bbs_cmvod` WHERE `url` LIKE '%91av.cyou%' ORDER BY `id` DESC
    
    $check = $myapp->t('cmvod')->fetch(array('url'=>'','url2'=>NULL));
    if(!empty($check)){
        $content = $myapp->getRequest($check['source'],false);
        if(!empty($content)){
            $content = stripcslashes($content);
            preg_match('/var\s*uul\s*=\s*[\"\'].+?\n/',$content,$m3u8);
            preg_match('/(\$|v=)?(http[\:\w\/\-\_\.]*\.m3u8)/',$m3u8[0],$m3url);
            if(!empty($m3url[2])){
                $myapp->t('cmvod')->update(array('url2'=>$m3url[2]),array('id'=>$check['id']));
                echo '<script>setTimeout(()=>{location.reload()},500);</script>';
            }
        }
    }
    print_r($check);
    exit;
endif;
if($myapp['router'][1]=='type'):
    $check = $myapp->t('cmvod')->all(array('%:type'=>'%国产传媒%'));
    file_put_contents($myapp->data['path']['upload'].'cmvodurl/data.json', json_encode($check, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    echo count($check);
    echo $myapp->debug();
    exit('ok');
endif;
if($myapp['router'][1]=='down'):
    $i = 1;
    $size = 1000;
    while(true){
        $check = $myapp->t('cmvod')->all('',array('order'=>array('id'=>'ASC'),'limit'=>array(($i-1)*$size,$size)));
        if(empty($check)){
            break;
        }
        $myapp->mkdir($myapp->data['path']['upload'].'cmvodurl/'.$i.'/');
        file_put_contents($myapp->data['path']['upload'].'cmvodurl/'.$i.'/data.json', json_encode($check, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $i+=1;

    }
    echo "OK";
    exit;
endif;
if($myapp['router'][1]=='recall'):
    $check = $myapp->t('cmvod')->all('',array('select'=>array('id','url','url2','img')));
    if(empty($check)){
        exit('error');
    }
    $img = array();
    $m3u8 = array();
    foreach($check as $k=>$v):
        $i = $v['id'];
        $dir = ceil($i/1000);
        /*
        $imgarr = explode('.',$v['img']);
        $imgurl = $av_root.$dir.'\\'.$i.'.'.array_pop($imgarr);
        if(!is_file($imgurl)){
            $imgdata = $myapp->getRequest($v['img']);
            if(!empty($imgdata)){
                file_put_contents(
                    $imgurl,
                    $imgdata
                );
                $img[]=$i;
            }elseif($imgdata===false){
                exit('img error'.$v['img'].$imgurl);
            }
        }
        */
        $url2 = $av_root2.$dir.'\\'.$i.'.m3u8';
        if((!empty($v['url2'])||preg_match('/91av\.cyou/',$v['url']))&&!is_file($url2)){
            $url_link = empty($v['url2'])?$v['url']:$v['url2'];
            $urldata = $myapp->getRequest($url_link);
            if(!empty($urldata)){
                echo $url2.PHP_EOL;
                echo $v['url'].'||'.$v['url2'].PHP_EOL;
                //exit;
                $urldata = preg_replace('/kkkkkkkk/','7.cdata.cc',$urldata);
                file_put_contents(
                    $url2,
                    $urldata
                );
                $m3u8[]=$i;
                if($v['url']&&!$v['url2']){
                    $myapp->t('cmvod')->update(array('url'=>'','url2'=>$v['url']),array('id'=>$v['id']));
                }
            }elseif($urldata===false){
                exit('url error');
            }
        }
    endforeach;
    print_r(array($img,$m3u8));
    echo "OK";
    exit;
endif;
$id = intval($myapp['router'][1]);
if(empty($id)) exit('error');
$source = 'https://cmvod.cyou/vod/play/id/'.$id.'/sid/1/nid/1/';
$status = false;
$info ='';
//https://cmvod.cyou/template/91av.php?v=https://91av.cyou/m3u8/video/218177.m3u8
/*
var reg = RegExp(/ed=null/);if(reg.exec(window.location.href)){url = url.replace(/kkkkkkkk/g,"7.cdata.cc");}else{}
var reg = RegExp(/ed=1/);if(reg.exec(window.location.href)){url = url.replace(/kkkkkkkk/g,"g.cdata.cc");}else{}
var reg = RegExp(/ed=2/);if(reg.exec(window.location.href)){url = url.replace(/kkkkkkkk/g,"strangetop.com");}else{}
var reg = RegExp(/ed=3/);if(reg.exec(window.location.href)){url = url.replace(/kkkkkkkk/g,"ovoxyz.com");}else{}
var reg = RegExp(/ed=4/);if(reg.exec(window.location.href)){url = url.replace(/kkkkkkkk/g,"c.9pvc.cc");}else{}
var reg = RegExp(/ed=5/);if(reg.exec(window.location.href)){url = url.replace(/kkkkkkkk/g,"b2.9pvc.cc");}else{}
var reg = RegExp(/ed=6/);if(reg.exec(window.location.href)){url = url.replace(/kkkkkkkk/g,"xuzx.xyz");}else{}
var reg = RegExp(/ed=7/);if(reg.exec(window.location.href)){url = url.replace(/kkkkkkkk/g,"cdata2.xyz");}else{}
*/
if($id>0):
    //$url = "https://cmvod.cyou";
    //$herf = $url.'/vod/show/class/%E5%9B%BD%E4%BA%A7%E4%BC%A0%E5%AA%92/id/'.$page.'/';
    $check = $myapp->t('cmvod')->fetch(array('id'=>$id));
    $av_root2 .= ceil($id/1000).'\\';
    $myapp->mkdir($av_root2);
    $av_root .= ceil($id/1000).'\\';
    $myapp->mkdir($av_root);
    if(empty($check)):
        $content = $myapp->getRequest($source,false,'kt_tcookie=1; https://cmvod.cyou/template/default/play.html?url=https://jkunbf.com/20240119/6LUEzZGa/index.m3u8=55;zone-cap-4854506:1%3B1707894888');
        //echo htmlentities($content);exit;
        if(!empty($content)):
            //echo stripcslashes($content);exit;
            //var uul ='高清$https://jkunbf.com/20240112/PZvFdofL/index.m3u8'+"m3u8文件";
            // var uul ='高清\$https://jkunbf\.com/20240112/PZvFdofL/index\.m3u8'\+"m3u8文件";
            //sleep(1);
            $content = stripcslashes($content);
            preg_match('/var\s*uul\s*=\s*[\"\'].+?\n/',$content,$m3u8);
            //print_r($m3u8);exit;
            if(!empty($m3u8[0])):
                preg_match('/(\$|v=)?(http[\:\w\/\-\_\.]*\.m3u8)/',$m3u8[0],$m3url);
                //print_r($m3url);exit;
                if(!empty($m3url[2])):
                    $m3u8URL = $m3url[2];
                    if(strpos($m3u8URL,'https://91av.cyou')!==false){
                        $m3u8_data = $myapp->getRequest($m3u8URL);
                        if(!empty($m3u8_data)):
                            $m3u8_data = preg_replace('/kkkkkkkk/','7.cdata.cc',$m3u8_data);
                            $m3u8URL = '';
                            file_put_contents($av_root2.$id.'.m3u8',$m3u8_data);
                            elseif($m3u8_data===false):
                            exit('m3u8 error');
                        endif;
                    }
                    preg_match('/name\=\"thumbnail\"\s*content\=\"(.+?)\"/',$content,$img);
                    preg_match('/<h1>(.+?)<\/h1>/',$content,$title);
                    preg_match('/str\="(.+?)"/',$content,$type);
                    $imgarr = explode('.',$img[1]);
                    $imgurl = $av_root.$id.'.'.array_pop($imgarr);
                    if(!is_file($imgurl)):
                        $imgdata = $myapp->getRequest($img[1]);
                        if(!empty($imgdata)){
                            file_put_contents(
                                $imgurl,
                                $imgdata
                            );
                        }elseif($imgdata===false){
                            exit('img erro');
                        }
                    endif;
                    $info .= '<p>'.$m3u8URL.'</p>';
                    $info .= '<p>'.$title[1].'</p>';
                    $info .= '<p>'.$type[1].'</p>';
                    $info .= '<p>'.$img[1].'</p>';
                    $myapp->t('cmvod')->insert(array(
                        'id'=>$id,
                        'title'=>$title[1],
                        'type'=>$type[1],
                        'url'=>$m3u8URL,
                        'url2'=>empty($m3u8URL)?$m3url[2]:'',
                        'img'=>$img[1],
                        'source'=>$source,
                    ),'id');
                    $status = true;
                else:
                    print_r($m3url);exit;
                endif;
            else:
                print_r(htmlentities($content));exit;
            endif;
        else:
            print_r(htmlentities($content));exit;
        endif;
    else:
        $status = true;
    endif;
else:
    exit('error');
endif;
$href = $myapp->url($myapp->data['router'][0].'-'.($id+1));
echo '<html><body>';
echo $info;
echo '<script>'.PHP_EOL;
if($status):
    echo 'setTimeout(()=>{location.href="'.$href.'"},2000);';
else:
    echo 'alert("'.$href.'")';
endif;
echo PHP_EOL.'</script>';
echo $myapp->debug().'</body></html>';
//<a href="/vod/play/id/16029/sid/1/nid/1/" title="XK-8092 《现任危机》 女友与前任的狂乱之夜">
//echo strlen($content);
//preg_match_all("/href\=\"(\/vod\/play\/id\/\d+\/sid\/1\/nid\/1\/)\"\stitle\=\"(.+?)\"/i",$content,$mathch);
//print_r($mathch[1]);

//$content2 = $myapp->getRequest($mathch[1][0]);
//preg_match_all("/href\=\"(\/vod\/play\/id\/\d+\/sid\/1\/nid\/1\/)\"\stitle\=\"(.+?)\"/i",$content,$mathch);

