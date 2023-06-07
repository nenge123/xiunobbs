<?php
defined('XIUNO')||die('return to <a href="">Home</a>');

$smtplist = array();
defined('XIUNO')||die('return to <a href="">Home</a>');
if($myapp['method']=='POST'&&!empty($_POST['host'])){
    foreach($_POST['host'] as $k=>$v){
        if(empty($v)||empty($_POST['email'][$k]) || empty($_POST['port'][$k]) || empty($_POST['user'][$k]) || empty($_POST['pass'][$k])){
            continue;
        }
        $smtplist[] = array(
            'email'=>trim($_POST['email'][$k]),
            'host'=>trim($v),
            'port'=>trim($_POST['port'][$k]),
            'user'=>trim($_POST['user'][$k]),
            'pass'=>trim($_POST['pass'][$k]),
        );
        $myapp->DB('Update','settings',array('value'=>serialize($smtplist)),array('name'=>'smtp'));
    }
}
if(empty($myapp['settings']['smtp'])){    
    $data = $myapp->DB('FetchRow','settings',array('name'=>'smtp'));
    if(!empty($data)){
        $smtplist = unserialize($data['value']);
    }
}else{
    $smtplist = $myapp['settings']['smtp'];
}
include $myapp->getTemplate('admin:smtp');