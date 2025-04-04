<?php
/**
 * @author N <m@nenge.net>
 * 设置
 * 网站信息
 */
!defined('ADMIN_PATH') and exit('Access Denied.');
// hook admin_settingindex_start.php
if ($_SERVER['REQUEST_METHOD'] == 'GET'):
	// hook admin_settingindex_get.php
	include(route_admin::tpl_link('setting/base.htm'));
elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
	// hook admin_settingindex_post.php
	#防止checkbox空值问题
	$_POST['user_create_on'] = MyApp::post('user_create_on',0);
	$_POST['user_create_email_on'] = MyApp::post('user_create_email_on',0);
	$_POST['user_resetpw_on'] = MyApp::post('user_resetpw_on',0);
	$_POST['runlevel'] = MyApp::post('runlevel',0);
	route_admin::format_post();
	// hook admin_settingindex_post_end.php
	file_replace_var(MyApp::path('conf/conf.php'), $_POST);
	// hook admin_settingindex_post_msg.php
	MyApp::message(0, MyApp::Lang('modify_successfully'));
endif;