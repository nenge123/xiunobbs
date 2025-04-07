<?php

/**
 * @author N <m@nenge.net>
 * 插件设置接口
 */
!defined('ADMIN_PATH') and exit('Access Denied.');
// 0:全部 1:公告(1.6版本已取消) 2:评论 3:系统(主题通知，关注通知等)  99:其他
// 接入的插件请务必按照规范的格式写
/**
 * 'list'=>array('url'=>url('notice-list'), 'text'=>MyApp::Lang('notice_admin_notice_list')),
 * 'post'=>array('url'=>url('notice-create'), 'text'=>MyApp::Lang('notice_admin_send_notice')),
 */
$plugin_menus = array(
	$plugin_dir=>MyApp::Lang('notice_admin_send_notice'),
	$plugin_dir.'/list'=>MyApp::Lang('notice_admin_notice_list'),
	$plugin_dir.'/list/1/read'=>'已读消息',
	$plugin_dir.'/list/1/unread'=>'未读消息',
);
$route_path =  __DIR__.'/route/admin/'.$plugin_action.'.inc.php';
if(is_file($route_path)):
	include $route_path;
	exit;
endif;
include __DIR__.'/route/admin/index.inc.php';
exit;