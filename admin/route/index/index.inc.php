<?php

/**
 * @author N <m@nenge.net>
 * 起始页
 * 统计信息
 */
!defined('APP_PATH') and exit('Access Denied.');
// hook admin_index_empty_start.php
$header['title'] = MyApp::Lang('admin_page');
$info = array();
$info['disable_functions'] = ini_get('disable_functions');
$info['allow_url_fopen'] = ini_get('allow_url_fopen') ? MyApp::Lang('yes') : MyApp::Lang('no');
$info['safe_mode'] = ini_get('safe_mode') ? MyApp::Lang('yes') : MyApp::Lang('no');
empty($info['disable_functions']) && $info['disable_functions'] = MyApp::Lang('none');
$info['upload_max_filesize'] = ini_get('upload_max_filesize');
$info['post_max_size'] = ini_get('post_max_size');
$info['memory_limit'] = ini_get('memory_limit');
$info['max_execution_time'] = ini_get('max_execution_time');
$info['dbversion'] = $db->version();
$info['SERVER_SOFTWARE'] = $_SERVER['SERVER_SOFTWARE'] ?? '';
$info['HTTP_X_FORWARDED_FOR'] = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
$info['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '';
$stat = array();
$stat['threads'] = thread_count();
$stat['posts'] = post_count();
$stat['users'] = user_count();
$stat['attachs'] = attach_count();
$stat['disk_free_space'] = function_exists('disk_free_space') ? humansize(disk_free_space(APP_PATH)) : MyApp::Lang('unknown');
// hook admin_index_empty_end.php
include(route_admin::tpl_link('index/home.htm'));
