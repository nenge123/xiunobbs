<?php

/**
 * @author N <m@nenge.net>
 * 设置
 * SMTP信息
 */
!defined('ADMIN_PATH') and exit('Access Denied.');
$smtp_path = MyApp::path('conf/smtp.conf.php');
// hook admin_settingsmtp_start.php
if ($_SERVER['REQUEST_METHOD'] == 'GET'):
	// hook admin_settingsmtp_get.php
	if(is_file($smtp_path)):
		$smtplist = include($smtp_path);
	else:
		$smtplist = array(
			array(
			'email'=>'',
			'host'=>'',
			'port'=>'',
			'user'=>'',
			'pass'=>'',
		));
	endif;
	// hook admin_settingsmtp_get_end.php
	include(route_admin::tpl_link('setting/smtp.htm'));
elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
	// hook admin_settingsmtp_post.php
	if(!empty($_POST)&&!empty($_POST['email'])):
		foreach($_POST['email']  as $k=>$v):
			$smtplist[$k] = array(
				'email' => $v,
				'host' => $_POST['host'][$k],
				'port' => $_POST['port'][$k],
				'user' => $_POST['user'][$k],
				'pass' => $_POST['pass'][$k],
			);
		endforeach;
		if(!empty($smtplist)):
			$r = file_put_contents_try(MyApp::path('conf/smtp.conf.php'), "<?php\r\nreturn " . var_export($smtplist, true) . ";\r\n?>");
			!$r and MyApp::message(-1, MyApp::Lang('conf/smtp.conf.php', array('file' => 'conf/smtp.conf.php')));
			// hook admin_settingsmtp_post_end.php
			MyApp::message(0, MyApp::Lang('save_successfully'));
		endif;
	endif;
	MyApp::message(-1, MyApp::Lang('data_not_changed'));
endif;
