<?php

/**
 * @author N <m@nenge.net>
 * 设置
 * 重写相关
 */
!defined('ADMIN_PATH') and exit('Access Denied.');
// hook admin_settingrewrite_start.php
if ($_SERVER['REQUEST_METHOD'] == 'GET'):
	// hook admin_settingrewrite_get.php
	include(route_admin::tpl_link('setting/rewrite.htm'));
elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
	// hook admin_setting_rewrite_post.php
	$_POST['url_rewrite_on'] = MyApp::post('url_rewrite_on', 0);
	$_POST['url_rewrite_style'] = MyApp::post('url_rewrite_style', 0);
	$_POST['cdn_on'] = MyApp::post('cdn_on', 0);
	route_admin::format_post();
	// hook admin_setting_rewrite_save.php
	file_replace_var(MyApp::path('conf/conf.php'), $_POST);
	MyApp::message(0, MyApp::Lang('modify_successfully'));
endif;
