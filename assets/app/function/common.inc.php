<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 基础函数
 */
use Nenge\APP;
/**
 * 获取语言
 */
function getLang($str,$arr=array())
{
    if(class_exists('Nenge\APP',false)):
        return APP::app()->getLang($str,$arr);
    endif;
    return $str;
}
/**
 * 获取数据库表对象
 */
function getTable($table,$plugin=''){
    return APP::app()->t($table,$plugin);
}
function getOnline(){
    $myapp = APP::app();
    $onlinelist = $myapp->plugin_read('onlinelist');
    if(!isset($onlinelist)):
        $onlinelist = $myapp->t('user')->online($myapp['time'] - settings_value('update_online',900));
    endif;
    return $onlinelist;
}
function getOnlineCount()
{
    return APP::app()->t('user')->count('WHERE `login_date` > ? ',[APP::app()->data['time'] - settings_value('update_online',900)]);
}
/**
 * 是否支持扩展
 */
function isExtension($extension)
{
    if (!extension_loaded($extension)):
        /*
        if (PHP_SHLIB_SUFFIX === 'dll'):
            return ini_set('extension','php_'.$extension.'.dll');
        else:
            return ini_set('extension',$extension.'.so');
        endif;
        */
        return false;
    endif;
    return true;
}
function isFloat($value){
    return ((int)$value != $value) ;
}
function isList(array $array)
{
    if(!is_array($array))return false;
    $key = array_keys($array);
    return (count($array)-1) === array_pop($key);
}
function settings_value($name,$default=''){
    $myapp = APP::app();
    if(isset($myapp ->data['settings'][$name])):
        if(!empty($default)):
            if($myapp ->data['settings'][$name]==''):
                return $default;
            endif;
            if(is_int($default))return intval($myapp ->data['settings'][$name]);
            if(is_float($default))return floatval($myapp ->data['settings'][$name]);
        endif;
        return $myapp ->data['settings'][$name];
    endif;
    return $default;
}
/**
 * 返回路由值
 */
function router_value($name,$default='')
{
    $router = APP::app()->data['router'];
    if(isset($router[$name])):
        if(!is_null($router[$name])):
            if($default&&empty($router[$name])) return $default;
            if(is_numeric($router[$name]) || gettype($default)=='integer'):
                return intval($router[$name]);
            endif;
            return $router[$name];
        endif;
    endif;
    return $default;
}
function router_set($name,$value='')
{
    APP::app()->data['router'][$name] = $value;
}
function router_is_active($a,$b,$c='active'){
    if(router_value($a) == $b):
        return ' '.$c;
    endif;
    return '';
}
function input_get($name,$default=''){
    $param = APP::app()->data['param'];
    $value = isset($param[$name])?$param[$name]:(isset($_GET[$name])?$_GET[$name]:'');
    if($value!=''&&$value!==false):
        return $value;
    endif;
    return $default;
}
function input_post($name,$default=''){
    $value = isset($_POST[$name])?$_POST[$name]:'';
    if($value!=''&&$value!==false):
        return $value;
    endif;
    return $default;
}
function autoload_class_handler($class)
{
    $arr = explode('\\', $class);
    $s = DIRECTORY_SEPARATOR;
    $root = APPROOT.'class'.$s;
    if ($arr[0] == 'PHPMailer') :
        return include($root. 'PHPMailer.php');
    endif;
    if ($arr[0] == 'ScssPhp') :
        return include($root.'ScssPhp.php');
    endif;
    if ($arr[0] == 'plugin') :
        throw new Exception('must running after Nenge\\APP::app().');
    endif;
    if(in_array($arr[0],array('lib','Nenge','table'))):
        return include($root.implode($s,$arr).'.php');
    endif;
}
function Location($site){
    header('Location: '.APP::app()->data['site']['root'].$site);
    exit;
}
function getRequest($url, $post = false, $cookie = array(), $refer = true, $timeout = 60,$header=array())
{
    //$url, $post = array(), $timeout = 30, $times = 1$header = []
    $ssl = stripos($url, 'https://') === 0 ? true : false;
    $curlObj = curl_init();
    $options = array();
    $header += array(
        'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Accept-Language:zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6',
        'Cache-Control:no-cache',
        'Dnt:1',
        'Pragma:no-cache',
        'Sec-Ch-Ua:"Not A(Brand";v="99", "Microsoft Edge";v="121", "Chromium";v="121"',
        'Sec-Ch-Ua-Mobile:?0',
        'Sec-Ch-Ua-Platform:"Windows"',
        'Sec-Fetch-Dest:document',
        'Sec-Fetch-Mode:navigate',
        'Sec-Fetch-Site:none',
        'Sec-Fetch-User:?1',
        'Upgrade-Insecure-Requests:1',
        'User-Agent:'.$_SERVER['HTTP_USER_AGENT'],
    );
    if (is_array($cookie)) $cookie = http_build_query($cookie);
    if (!empty($cookie)) {
        //$header[] = 'Content-type: application/x-www-form-urlencoded';
        $header[] = "Cookie: $cookie";
        $options[CURLOPT_COOKIE] = $cookie;
    }
    $options += array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_AUTOREFERER => 1,
        CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'],
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0,
        CURLOPT_HTTPHEADER => $header,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_REFERER => $url, //伪造来路
        CURLOPT_RETURNTRANSFER=>1,
    );
    //returnData
    if ($refer) {
        $options[CURLOPT_REFERER] = $refer;
    }
    if ($ssl) {
        //support https
        $options[CURLOPT_SSL_VERIFYHOST] = false;
        $options[CURLOPT_SSL_VERIFYPEER] = false;
        $options[CURLOPT_SSLVERSION] = 3;
        $options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2;
    }
    if ($post) {
        $options[CURLOPT_POST] = 1;
        if (is_array($post)) {
            $options[CURLOPT_POSTFIELDS] =  json_encode($post);
        } else {
            $options[CURLOPT_HTTPHEADER]+=array(
                'accept: application/json',
                'Content-Type:application/json;charset=utf-8',
                'Content-Length:' . strlen($post), 'accept:application/json'
            );
            $options[CURLOPT_POSTFIELDS] =  $post;
        }
    }
    curl_setopt_array($curlObj, $options);
    $returnData = curl_exec($curlObj);
    $request = curl_getinfo($curlObj);
    if ($request['http_code']!==200||curl_errno($curlObj)) {
        //error message
        if(defined('DEBUG')):
        //    echo '<pre>';
        //    print_r( curl_error($curlObj));
        //    print_r($request);
        //    exit;
        endif;
        $returnData = null;
    }
    curl_close($curlObj);
    return $returnData;
}
/**
 * 输出安全HTML字符
 *
 * @param string $html
 * @param boolean $ishtml
 * @return mixed
 */
function safeHTML(string $html,bool $ishtml=true):mixed{
    $myapp = APP::app();
    if(!empty($result = $myapp->plugin_read('safe_html',$html,$ishtml))):
        return $result;
    endif;
    $xss = true;
    $style = array('position' => 'relative');
    $charset =  empty($myapp->conf['charset'])?'UTF-8':$myapp->conf['charset'];
    $doc = new DOMDocument('1.0',$charset);
    #$doc->formatOutput = true;
    #$doc->encoding =  $charset;
    $doc->preserveWhiteSpace = false;
    #$doc->resolveExternals = false;
    #$doc->substituteEntities = false;
    #$doc->strictErrorChecking = false;
    $doc->loadHTML('<!DOCTYPE html><html><head><meta charset="' .$charset. '"></head><body>'.$html.'</body></html>', LIBXML_ERR_NONE|LIBXML_NOERROR);
    $dorp_node = function(&$nodes) use (&$dorp_next){
        if(empty($nodes->length)) return;
        #$tag = array('script', 'style', 'link', 'iframe');
        foreach (iterator_to_array($nodes) as $node) :
            $name = $node->nodeName;
            if ($node->nodeType === 1) :
                if(in_array($name,array('meta','head'))):
                    $node->parentNode->removeChild($node);
                    continue;
                endif;
                #if (in_array($name, $tag)) :
                #过滤脚本 为被动式
                #if($name == 'script'):
                    #$node->parentNode->removeChild($node);
                    #continue;
                #endif;
                #过滤iframe 为被动式
                if($name == 'iframe'||$name == 'script'):
                    if($name=='script'):
                        if(!empty($node?->attributes->getNamedItem('src'))):
                            $node->parentNode->removeChild($node);
                            continue;
                        endif;
                    endif;
                    $newElm = $node->ownerDocument->createElement('app-'.$name);
                    foreach(iterator_to_array($node->attributes) as $attr):
                        #$newElm->setAttribute($attr->nodeName,$attr->nodeValue);
                        $newElm->setAttributeNode($attr);
                    endforeach;
                    $newElm->setAttributeNode(new DOMAttr('hidden'));
                    foreach(iterator_to_array($node->childNodes) as $child):
                        $newElm->appendChild($child);
                    endforeach;
                    $node->parentNode->replaceChild($newElm,$node);
                    unset($node);
                    $dorp_next($newElm);
                    continue;
                endif;
                $dorp_next($node);
            #elseif ($node->nodeType === 3||$node->nodeType === 4) :
                #过滤多余换行
            #    if(isset($node->parentNode->nodeName)&&!in_array($node->parentNode->nodeName,array('pre','code'))):
            #        $node->nodeValue = trim($node->nodeValue,"\n\r\t");
            #    endif;
            endif;
        endforeach;
    };
    $dorp_next = function(&$node) use(&$dorp_node,&$dorp_attr){
        $dorp_attr($node);
        if (isset($node->childNodes)) :
            $dorp_node($node->childNodes);
        endif;
    };
    $dorp_style = function (&$attribute)use($style){
        if (!empty($attribute->value)) :
            $value = explode(';', $attribute->value);
            $output = '';
            foreach ($value as $value) :
                $prop = explode(':', trim($value));
                if (!empty($prop[0])) :
                    $propName = trim($prop[0]);
                    $propValue = empty($prop[1]) ? '' : trim($prop[1]);
                    if (isset($style[$propName])) :
                        $propData = $style[$propName];
                        if (empty($propData) || $propData === false) :
                            continue;
                        elseif (is_array($propData) && !in_array($propValue, $propData)) :
                            continue;
                        endif;
                        if (is_string($propData) && $propValue != $propData) :
                            $propValue = $propData;
                        endif;
                    endif;
                    $output .= $propName . ':' . $propValue . ';';
                endif;
            endforeach;
            if (!empty($output)) :
                $attribute->value = $output;
                return false;
            endif;
        endif;
        return true;
    };
    $dorp_attr = function(&$node)use(&$xss,&$dorp_style){
        if (isset($node->attributes) && $node->attributes->length > 0) :
            foreach (iterator_to_array($node->attributes) as $attribute) :
                $name = $attribute->name;
                $value = $attribute->value;
                #$value = htmlspecialchars_decode($value);
                if ($xss) :
                    #XSS 默认开启 过滤事件 onxxx 剔除id属性
                    if(stripos($name,'on') === 0||stripos($name,'data-on') === 0||$name=='id'):
                        #
                        $node->removeAttribute($name);
                        continue;
                    endif;
                    #XSS
                    if (stripos($value,'javascript')===0||stripos($value,'data:')===0):
                        #javascript:....;
                        #href="data:text/html;base64,js code...
                        $node->removeAttribute($name);
                        continue;
                    endif;
                    #if (stripos($value,'eval')!==false||stripos($value,'function')!==false):
                        #eval function ()=>;
                    #    $node->removeAttribute($name);
                    #    continue;
                    #endif;
                    #if (in_array($name, array('src', 'href'))) :
                    #    if(!preg_match('/^(http:)?[^;\(\)\:]+$/i',$value)):
                    #        $attribute->value = '#';
                    #    endif;
                    #endif;
                endif;
                #$attribute->value = $value;
                #if ($name == 'style' && $dorp_style($attribute)) :
                    #CSS 过滤
                #    $node->removeAttribute($name);
                #endif;
            endforeach;
        endif;
    };
    $dorp_node($doc->documentElement->childNodes);
    $result = substr($doc->saveHTML($doc->documentElement->firstChild),6,-7);
    return $result;
    $body = $doc->getElementsByTagName('body')[0];
    if($ishtml):
        $html = '';
        if(empty($body->childNodes->length)) return $html;
        foreach (iterator_to_array($body->childNodes) as $outnode):
            $html .= $doc->saveHTML($outnode);
        endforeach;
        return $html;
    endif;
    return $body;

}
function ip2str($ip):string{
    $bin = bin2hex(inet_pton(trim($ip)));
    if(strlen($bin)==8):
        return hexdec($bin);
    endif;
    return $bin;
}
function str2ip($str):string{

    if(is_numeric($str)&&strlen($str)==10):
        return long2ip($str);
    endif;
    return inet_ntop(hex2bin($str));
}
function pagination(int $total,$page=1,int $limit=40,int $maxnum=8)
{
    
    $left = array();
    $right = array();
    $maxpage = ceil($total/$limit);
    $maxlengh = $maxnum;
    if(is_string($page)):
        $page = intval($page);
    endif;
    if($page<1)$page = 1;
    for($i=0;$i<=$maxnum;$i++):
        if($i==0||$page+$i<$maxpage):
            if($page+$i==$maxpage||$page+$i==1):
                continue;
            endif;
            $num = $page+$i;
            $right[] = $num;
            $maxlengh--;
        endif;
        if ($maxlengh < 0):
            break;
        endif;
        if($i>0&&$page-$i>1):
            $num = $page-$i;
            array_unshift($left,$num);
            $maxlengh--;
        endif;
        if ($maxlengh < 0):
            break;
        endif;
    endfor;
    return array_merge($left,$right);
}
/**
 * 附件下载
 *
 * @param type $attach
 * @return void
 */
function down_attach($attach,$attachfile='')
{
    $myapp = APP::app();
    if(empty($attachfile)):
        $attachfile = $myapp->get_dir_path($myapp->data['path']['attach'].$attach['orgfilename']);
    endif;
    $fp = fopen($attachfile, "rb");
    $fstat = fstat($fp);
    header_remove("Set-Cookie");
    $fileinfo = pathinfo($attach['orgfilename']);
    $extension = $fileinfo['extension'];
    $myapp->t('attach')->add_download($attach['aid'],1);
    ob_clean();
    #header('Cache-control: max-age=0, must-revalidate, post-check=0, pre-check=0');
    #header('Cache-control: max-age=86400');
    header('Cache-control: no-cache');
    #header("Pragma: public");
    #header('Date:'.gmdate('r',$attach['create_date']));
    if (!empty($data['isimage']) ||in_array($extension,array('gif', 'jpg', 'jpeg','bmp','webp','png','avif','apng')) ) {
        header('Content-type: image/' . $extension);
    } else if (in_array($extension,array('zip', '7z', 'rar'))) {
        header('Content-type: application/x-' . $extension . '-compressed');
    } else {
        header('Content-type: application/octet-stream');
    }
    header('Content-Transfer-Encoding: binary');
    header('Content-Disposition: attachment;filename="'.urlencode($fileinfo['basename']).'"');
    header('Content-Length: ' . $fstat['size']);
    header('Accept-Ranges:bytes');
    #header('Date:'.gmdate('D, d M Y H:i:s',$fstat['ctime']).' GMT');
    header('Last-Modified:'.gmdate('D, d M Y H:i:s',$fstat['mtime']).' GMT');
    #header('Last-Modified:'.date('D, d M Y H:i:s \\G\\M\\TO',$fstat['mtime']));
    #header('Content-Type: application/octet-stream');
    fpassthru($fp);
    fclose($fp);
    $myapp->exit();
}
/**
 * 插件名/插件类名 快速实例化
 *
 * @param string $plugin
 * @return object|null
 */
function P(string $plugin,...$param):object|null
{
    $arr = explode('/',$plugin);
    $plugindir = array_shift($arr);
    $pluginName = str_replace('-','_',$plugindir);
    $pluginName = str_replace('#','_',$pluginName);
    $pluginName = '\\plugin\\'.$pluginName.'\\'.implode('\\',$arr);
    if(!class_exists($pluginName,false)):
        $path = $plugindir.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,$arr).'.class.php';
        include(WEBROOT.'plugin'.DIRECTORY_SEPARATOR.$path);
    endif;
    return call_user_func_array(array($pluginName,'app'),$param);
}