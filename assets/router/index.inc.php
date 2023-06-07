<?php
defined('XIUNO')||die('return to <a href="">Home</a>');
$page =1;
if(!empty($myapp->data['router'][$router_name])){
    $page = $myapp->data['router'][$router_name]?:1;
}
$forum_id = 0;
// hook test
$myapp->data['title']= $language['index_page'];
include $myapp->template('index');