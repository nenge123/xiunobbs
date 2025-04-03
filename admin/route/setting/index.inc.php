<?php
/**
 * @author N <m@nenge.net>
 * 设置
 * 网站信息
 */
!defined('APP_PATH') and exit('Access Denied.');
// hook admin_settingindex_start.php
if ($_SERVER['REQUEST_METHOD'] == 'GET'):
	// hook admin_settingindex_get.php
	include _include(ADMIN_PATH . 'view/htm/setting/base.htm');
elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
	// hook admin_settingindex_post.php
	#防止checkbox空值问题
	$_POST['user_create_on'] = MyApp::post('user_create_on',0);
	$_POST['user_create_email_on'] = MyApp::post('user_create_email_on',0);
	$_POST['user_resetpw_on'] = MyApp::post('user_resetpw_on',0);
	$_POST['runlevel'] = MyApp::post('runlevel',0);
	route_admin::format_post();
	// hook admin_settingindex_post_end.php
	file_replace_var(APP_PATH . 'conf/conf.php', $_POST);
	// hook admin_settingindex_post_msg.php
	MyApp::message(0, lang('modify_successfully'));
endif;