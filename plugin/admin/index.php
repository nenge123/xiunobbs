<?php
defined('XIUNO')||die('return to <a href="">Home</a>');
use Nenge\APP;
use Nenge\DB;
use Nenge\db_mysqli;
    if(empty($myapp->data)){
        $myapp->exit('','/');
    }
?>