<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 首页
 */
defined('WEBROOT') or die('return to <a href="">Home</a>');
if(empty($myapp->data['settings']['index_show']) ||$myapp->data['settings']['index_show']=='threads'):    
    $myapp->data['title']= $language['index_page'];
    include $myapp->template('index');
endif;