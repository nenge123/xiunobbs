<?php

/**
 * @author N <m@nenge.net>
 * 插件设置接口
 */
!defined('ADMIN_PATH') and exit('Access Denied.');
// 0:全部 1:公告(1.6版本已取消) 2:评论 3:系统(主题通知，关注通知等)  99:其他
// 接入的插件请务必按照规范的格式写
/**
 * 'list'=>array('url'=>url('notice-list'), 'text'=>lang('notice_admin_notice_list')),
 * 'post'=>array('url'=>url('notice-create'), 'text'=>lang('notice_admin_send_notice')),
 */
$page = param(2, 1);
$pagesize = 20;
$active = 'default';
$notices = notice_count(); //直接获取最新的
$cond = array();
$orderby = 'nid';

$notice_menu = include _include(APP_PATH.'plugin/huux_notice/conf/notice_menu.conf.php');
$noticelist = notice_find($cond, $page, $pagesize);
$pagination = pagination(url("notice-list-{page}"), $notices, $page, $pagesize);

$header['title'] = MyApp::Lang('notice_admin_notice_list');
$header['mobile_title'] = MyApp::Lang('notice_admin_notice_list');

include _include(APP_PATH."plugin/huux_notice/view/htm/admin_notice_list.htm");