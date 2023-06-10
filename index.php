<?php
/*
 * Copyright (C) Nenge.net
#对于不使用其他网站资源,并且不允许其他网站干涉 例如XSS等跨域攻击.可开启此项
#隔离iframe same-origin非同源 隔离到另一个浏览进程
header('Cross-Origin-Embedder-Policy: require-corp');
header('Cross-Origin-Opener-Policy: same-origin');
#运行使用其他跨域资源 如CDN. same-site 同一站点,same-origin同源,
#如果自有CDN,设置cross-origin,那么CDN中亦需要Cross-Origin-Resource-Policy: cross-origin
header('Cross-Origin-Resource-Policy: same-site');
*/
define('XIUNO',true);
use Nenge\APP;
include(__DIR__.'\\assets\\class\\app.php');
$myapp = APP::app();
#error_reporting(E_ALL);
#print_r($myapp->data['router']);
#print_r(Nenge\DB::t('table_count')->add_count(array('post3'=>1)));
/*echo preg_replace('/^<\?php\s(.+)\?>$/is','\\1',php_strip_whitespace($myapp->data['path']['plugin'].'admin/include/test.txt'));*/
if(!empty($myapp->data)){
    define('DEBUG',$myapp->conf['debug']);
    $language = Nenge\language::app();
    $router_list = $myapp->data['router_list'];
    $router_name = $myapp->data['router'][0];
    $router_value = $myapp->data['router'][$router_name];
    #(int) \Nenge\DB::app();
    #Nenge\DB::t('user')->connect()->query('fff');
    #$myapp->session_login(1);
    #print_r($myapp->data['tokens']);
    include $myapp->router($myapp->data['router'][0]);
}else if(is_file(__DIR__.'\\plugin\install\\index.php')){
    include __DIR__.'\\plugin\\install\\index.php';
}
$myapp->exit();
?>