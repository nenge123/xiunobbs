<?php

/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 板块列表 板块帖子
 */
defined('WEBROOT') or die('return to <a href="">Home</a>');
$av_root = $myapp->data['path']['upload'] . 'timizy\\img\\';
$av_root2 = $myapp->data['path']['upload'] . 'timizy\\m3u8\\';
$error = '{"error":"Operation timed out after 60015 milliseconds with 0 bytes received"}';
if ($myapp['router'][1] == 'check') :
    //SELECT * FROM `bbs_timizy` WHERE `url` LIKE '%91av.cyou%' ORDER BY `id` DESC

    $check = $myapp->t('timizy')->fetch(array('%:url' => '%91av.cyou%'));
    if (!empty($check)) {
        $m3u8_data = $myapp->getRequest($check['url']);
        if (!empty($m3u8_data)) :
            $m3u8_data = preg_replace('/kkkkkkkk/', '7.cdata.cc', $m3u8_data);
            $m3u8URL = $check['id'] . '.m3u8';
            file_put_contents($av_root2 . $m3u8URL, $m3u8_data);
            $myapp->t('timizy')->update(array('url' => $m3u8URL), array('id' => $check['id']));
            echo '<h1>OK</h1><script>setTimeout(()=>{location.reload()},1000);</script>';
            echo $myapp->debug();
        endif;
    }
    print_r($check);
    exit;
endif;
if ($myapp['router'][1] == 'redown') :
    //SELECT * FROM `bbs_timizy` WHERE `url` LIKE '%91av.cyou%' ORDER BY `id` DESC

    $check = $myapp->t('timizy')->fetch(array('url' => '', 'url2' => NULL));
    if (!empty($check)) {
        $content = $myapp->getRequest($check['source'], false);
        if (!empty($content)) {
            $content = stripcslashes($content);
            preg_match('/var\s*uul\s*=\s*[\"\'].+?\n/', $content, $m3u8);
            preg_match('/(\$|v=)?(http[\:\w\/\-\_\.]*\.m3u8)/', $m3u8[0], $m3url);
            if (!empty($m3url[2])) {
                $myapp->t('timizy')->update(array('url2' => $m3url[2]), array('id' => $check['id']));
                echo '<script>setTimeout(()=>{location.reload()},500);</script>';
            }
        }
    }
    print_r($check);
    exit;
endif;
if ($myapp['router'][1] == 'down') :
    $i = 1;
    $size = 1000;
    while (true) {
        $check = $myapp->t('timizy')->all('', array('order' => array('id' => 'ASC'), 'limit' => array(($i - 1) * $size, $size)));
        if (empty($check)) {
            break;
        }
        $myapp->mkdir($myapp->data['path']['upload'] . 'timizyurl/' . $i . '/');
        file_put_contents($myapp->data['path']['upload'] . 'timizyurl/' . $i . '/data.json', json_encode($check, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $i += 1;
    }
    echo "OK";
    exit;
endif;
if ($myapp['router'][1] == 'recall') :
    $check = $myapp->t('timizy')->all('', array('select' => array('id', 'url', 'url2', 'img')));
    if (empty($check)) {
        exit('error');
    }
    $img = array();
    $m3u8 = array();
    foreach ($check as $k => $v) :
        $i = $v['id'];
        $dir = ceil($i / 1000);
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
        $url2 = $av_root2 . $dir . '\\' . $i . '.m3u8';
        if ((!empty($v['url2']) || preg_match('/91av\.cyou/', $v['url'])) && !is_file($url2)) {
            $url_link = empty($v['url2']) ? $v['url'] : $v['url2'];
            $urldata = $myapp->getRequest($url_link);
            if (!empty($urldata)) {
                echo $url2 . PHP_EOL;
                echo $v['url'] . '||' . $v['url2'] . PHP_EOL;
                //exit;
                $urldata = preg_replace('/kkkkkkkk/', '7.cdata.cc', $urldata);
                file_put_contents(
                    $url2,
                    $urldata
                );
                $m3u8[] = $i;
                if ($v['url'] && !$v['url2']) {
                    $myapp->t('timizy')->update(array('url' => '', 'url2' => $v['url']), array('id' => $v['id']));
                }
            } elseif ($urldata === false) {
                exit('url error');
            }
        }
    endforeach;
    print_r(array($img, $m3u8));
    echo "OK";
    exit;
endif;
$id = intval($myapp['router'][1]);
if (empty($id)) exit('error');
$source = 'https://timizy10.cc/index.php/vod/detail/id/' . $id . '.html';
$status = false;
$info = '';
//https://timizy.cyou/template/91av.php?v=https://91av.cyou/m3u8/video/218177.m3u8
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
if ($id > 0) :
    //$url = "https://timizy.cyou";
    //$herf = $url.'/vod/show/class/%E5%9B%BD%E4%BA%A7%E4%BC%A0%E5%AA%92/id/'.$page.'/';
    $check = $myapp->t('timizy')->fetch(array('id' => $id));
    if (empty($check)) :
        $content = $myapp->getRequest($source, false, array('kt_tcookie' => 1));
        if ($content) $content = trim($content);
        //echo stripcslashes($content);exit;
        if (!empty($content)) :
            //var uul ='高清$https://jkunbf.com/20240112/PZvFdofL/index.m3u8'+"m3u8文件";
            // var uul ='高清\$https://jkunbf\.com/20240112/PZvFdofL/index\.m3u8'\+"m3u8文件";
            //sleep(1);
            $content = stripcslashes($content);
            preg_match_all('/\$(http.+?\.m3u8)/i', $content, $m3u8);
            //print_r($m3u8);exit;
            if (!empty($m3u8[1])) :
                //preg_match('/(\$|v=)?(http[\:\w\/\-\_\.]*\.m3u8)/',$m3u8[0],$m3url);
                //print_r($m3url);exit;
                //if(!empty($m3url[2])):
                preg_match('/<div class=\"left\">n?\r?\s*<img\ssrc=\"(.+?)\">/is', $content, $img);
                preg_match('/名称：(.+?)<\/p>/', $content, $title);
                preg_match('/<a href=\"\/index\.php\/vod\/type\/id\/\d+\.html\">(.+?)<\/a>/is', $content, $type);
                preg_match('/<p>片长：(.+?)<\/p>/', $content, $time);
                preg_match('/<p>更新时间：(.+?)<\/p>/', $content, $date);
                //echo strlen($content);print_r($img);print_r($title);print_r($type);print_r($date);exit;
                //$imgurl = 'https://timizy10.cc'.$img[1];
                $av_root .= ceil($id / 1000) . '\\';
                $myapp->mkdir($av_root);
                $img_src = $img[1];
                if (strpos($img_src, '/') === 0) {
                    $img_src = 'https://timizy10.cc' . $img[1];
                }
                $imgarr = explode('.', $img_src);
                $imgurl = $av_root . $id . '.' . array_pop($imgarr);
                if (!is_file($imgurl)) :
                    $imgdata = $myapp->getRequest($img_src);
                    if (!empty($imgdata)) {
                        file_put_contents(
                            $imgurl,
                            $imgdata
                        );
                    } elseif ($imgdata === false) {
                        exit('img erro');
                    }
                endif;
                $info .= '<p>' . implode(',', $m3u8[1]) . '</p>';
                $info .= '<p>' . $title[1] . '</p>';
                $info .= '<p>' . $type[1] . '</p>';
                $info .= '<p>' . $img_src . '</p>';
                //echo $info;exit;
                $myapp->t('timizy')->insert(array(
                    'id' => $id,
                    'title' => $title[1],
                    'type' => $type[1],
                    'url' => implode(',', $m3u8[1]),
                    'img' => $img_src,
                    'time' => $time[1],
                    'date' => strtotime($date[1]),
                ), 'id');
                $status = true;
            //else:
            //print_r($m3url);exit;
            //endif;
            else :
                print_r(htmlentities($content));
                exit;
            endif;
        else :
            exit('404');
        endif;
    else :
        $status = true;
    endif;
else :
    exit('error');
endif;
$href = $myapp->url($myapp->data['router'][0] . '-' . ($id - 1));
echo '<html><body>';
echo $info;
echo '<script>' . PHP_EOL;
if ($status) :
    echo 'setTimeout(()=>{location.href="' . $href . '"},500);';
else :
    echo 'alert("' . $href . '")';
endif;
echo PHP_EOL . '</script>';
echo $myapp->debug() . '</body></html>';
//<a href="/vod/play/id/16029/sid/1/nid/1/" title="XK-8092 《现任危机》 女友与前任的狂乱之夜">
//echo strlen($content);
//preg_match_all("/href\=\"(\/vod\/play\/id\/\d+\/sid\/1\/nid\/1\/)\"\stitle\=\"(.+?)\"/i",$content,$mathch);
//print_r($mathch[1]);

//$content2 = $myapp->getRequest($mathch[1][0]);
//preg_match_all("/href\=\"(\/vod\/play\/id\/\d+\/sid\/1\/nid\/1\/)\"\stitle\=\"(.+?)\"/i",$content,$mathch);
