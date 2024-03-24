<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 对于不使用其他网站资源,并且不允许其他网站干涉 例如XSS等跨域攻击.可开启此项
 * 隔离iframe same-origin非同源 隔离到另一个浏览进程
 * header('Cross-Origin-Embedder-Policy: require-corp');
 * header('Cross-Origin-Opener-Policy: same-origin');
 * 运行使用其他跨域资源 如CDN. same-site 同一站点,same-origin同源,
 * 如果自有CDN,设置cross-origin,那么CDN中亦需要Cross-Origin-Resource-Policy: cross-origin
 * header('Cross-Origin-Resource-Policy: same-site');
*/
define('DS',DIRECTORY_SEPARATOR);
define('WEBROOT',__DIR__.DS); #必须定义
define('WEBSITE',str_replace('\\','/',str_replace(realpath($_SERVER['DOCUMENT_ROOT']),'',__DIR__)).'/'); #必须定义
define('APPROOT',WEBROOT.'assets'.DS.'app'.DS); #必须定义
define('DEBUG',true); #可选,与值无关,定义后启动调试模式
#define('MINI',true); #可选,与值无关,定义后压缩默认类大大减少文件加载数量
error_reporting(!defined('DEBUG')?0:E_ALL);
if(defined('MINI')&&!defined('DEBUG')):
#把核心文件堆放在一起,減少文件加载
    $class_file = APPROOT.'cache'.DS.'data'.DS.'all-class.php';
    if(!is_file($class_file)):
        include (APPROOT.'function'.DS.'help.inc.php');
        miniclass($class_file);
    endif;
    include($class_file);
endif;
#注册 类自动加载
spl_autoload_register(function($class){
    $arr = explode('\\', $class);
    if ($arr[0] == 'PHPMailer') :
        return include(APPROOT.'class'.DS. 'PHPMailer.php');
    endif;
    if ($arr[0] == 'ScssPhp') :
        return include(APPROOT.'class'.DS.'ScssPhp.php');
    endif;
    if ($arr[0] == 'plugin'):
        array_shift($arr);
        $dir = array_shift($arr);
        $path = WEBROOT.'plugin'.DS.$dir.DS.'class'.DS.implode(DS,$arr).'.class.php';
        if(!(include($path))):
            throw new Exception('plugin path is error!');
        endif;
    endif;
    if(in_array($arr[0],array('lib','Nenge','table'))):
        return include(APPROOT.'class'.DS.implode(DS,$arr).'.php');
    endif;
}, true, true);
$myapp =  Nenge\APP::app(); #初始化
$language = Nenge\language::app();#初始化语言快捷读取接口(并未直接读取语言包,只有使用时加载)
#print_r($myapp->plugin);
#exit;
if(!empty($myapp->conf)):
    include $myapp->get_router_link();
    $myapp->exit();
endif;
$intall = WEBROOT.'plugin'.DS.'install'.DS.'index.inc.php';
if(is_file($intall)):
    include(WEBROOT.'plugin'.DS.'install'.DS.'index.inc.php');
endif;
$myapp->exit();