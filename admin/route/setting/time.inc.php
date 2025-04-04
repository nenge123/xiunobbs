<?php

/**
 * @author N <m@nenge.net>
 * 设置
 * 时间相关
 */
!defined('APP_PATH') and exit('Access Denied.');
// hook admin_settingtime_get_post.php
if ($_SERVER['REQUEST_METHOD'] == 'GET'):
	// hook admin_settingtime_get.php
	include(route_admin::tpl_link('setting/time.htm'));
elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
	// hook admin_settingtime_post.php
	route_admin::format_post();
	// hook admin_settingtime_post_save.php
	file_replace_var(APP_PATH . 'conf/conf.php', $_POST);
	MyApp::message(0, MyApp::Lang('modify_successfully'));
endif;