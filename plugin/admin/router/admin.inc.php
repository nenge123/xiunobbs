<?php
defined('XIUNO')||die('return to <a href="">Home</a>');
if(!empty($myapp['user']['gid'])&&$myapp['user']['gid']==1){
    if(empty($myapp->router->act)){
        $myapp->router->act = 'index';
    }
    #$myapp->language->siteData('bbs');
    $myapp->language->pluginData('admin');
    include $myapp->path->plugin.'admin/router/'.$myapp->router->act.'.inc.php';
    $myapp->exit();
}