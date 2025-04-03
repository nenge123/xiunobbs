<?php

/**
 * @author N <m@nenge.net>
 * 起始页
 * 登录
 */
!defined('APP_PATH') and exit('Access Denied.');
// hook admin_index_login_get_post.php
MyApp::setValue('title', MyApp::Lang('admin_login'));
if ($_SERVER['REQUEST_METHOD'] == 'GET'):
	// hook admin_index_login_get_start.php
	include _include(ADMIN_PATH . "view/htm/index/login.htm");
elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
	// hook admin_index_login_post_start.php
	$password = MyApp::post('password');
	if (hash_equals(md5($password . $user['salt']), $user['password'])):
		route_admin::admin_token_set();
		xn_log('login successed. uid:' . $user['uid'], 'admin_login');
		// hook admin_index_login_post_end.php
		MyApp::message(0, MyApp::Lang('login_successfully'), ['url' => MyApp::url('index')]);
	else:
		xn_log('password error. uid:' . $user['uid'] . ' - ******' . substr($password, -6), 'admin_login_error');
		#密码错误
		MyApp::message('password', MyApp::Lang('password_incorrect'));
	endif;
endif;
