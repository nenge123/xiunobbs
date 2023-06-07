<?php
    defined('XIUNO')||die();
    if($myapp->data['method'] == 'POST'&&!empty($_POST['hash'])&&!empty($_POST['action'])&&$_POST['hash']==$myapp->data['hash']){
        $action = explode('-',$_POST['action']);
        $routerFile = $myapp->File('api/'.basename($action[0]).'.inc.php');
        if($routerFile){
            include $routerFile;
        }
    }
    $myapp->exit();
?>