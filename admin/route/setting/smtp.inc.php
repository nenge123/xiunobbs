<?php

/**
 * @author N <m@nenge.net>
 * 设置
 * SMTP信息
 */
!defined('APP_PATH') and exit('Access Denied.');
include \plugin::parseFile(APP_PATH . 'model/smtp.func.php');
$smtplist = smtp_init(APP_PATH . 'conf/smtp.conf.php');
// hook admin_settingsmtp_start.php
if ($_SERVER['REQUEST_METHOD'] == 'GET'):
	// hook admin_settingsmtp_get.php
	$smtplist = smtp_find();
	$maxid = smtp_maxid();
	$importjs[] = route_admin::site('view/js/smtp.js');
	// hook admin_settingsmtp_get_end.php
	include(route_admin::tpl_link('setting/smtp.htm'));
elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
	// hook admin_settingsmtp_post.php
	$email = param('email', array(''));
	$host = param('host', array(''));
	$port = param('port', array(0));
	$user = param('user', array(''));
	$pass = param('pass', array(''));
	$smtplist = array();
	foreach ($email as $k => $v):
		$smtplist[$k] = array(
			'email' => $email[$k],
			'host' => $host[$k],
			'port' => $port[$k],
			'user' => $user[$k],
			'pass' => $pass[$k],
		);
	endforeach;
	$r = file_put_contents_try(APP_PATH . 'conf/smtp.conf.php', "<?php\r\nreturn " . var_export($smtplist, true) . ";\r\n?>");
	!$r and MyApp::message(-1, MyApp::Lang('conf/smtp.conf.php', array('file' => 'conf/smtp.conf.php')));
	// hook admin_settingsmtp_post_end.php
	MyApp::message(0, MyApp::Lang('save_successfully'));
endif;
