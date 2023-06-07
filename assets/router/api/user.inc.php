<?php
    defined('XIUNO')||die('return to <a href="">Home</a>');
    if(!empty($_POST['uid'])){
        $ip = $myapp->DB('FetchValue','user','login_ip',array('uid'=>intval($_POST['uid'])));
        if($ip){
            $ip = $myapp->long2ip($ip);
            //'http://ip.zxinc.org/api.php?type=json&ip='.$ip;
        }
    }
?>