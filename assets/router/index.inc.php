<?php
defined('XIUNO')||die('return to <a href="">Home</a>');
$myapp->data['title']= $language['index_page'];
// hook router_index
include $myapp->template('index');