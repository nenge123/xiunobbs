<?php

!defined('APP_PATH') and exit('Access Denied.');

$action = MyApp::value(0);

include _include(APP_PATH . 'model/smtp.func.php');
$smtplist = smtp_init(APP_PATH . 'conf/smtp.conf.php');
// hook admin_setting_start.php
switch ($action):
	case 'smtp':
		// hook admin_setting_smtp_get_post.php
		if ($_SERVER['REQUEST_METHOD'] == 'GET'):
			// hook admin_setting_smtp_get_start.php
			$header['title'] = lang('admin_setting_smtp');
			$header['mobile_title'] = lang('admin_setting_smtp');
			$smtplist = smtp_find();
			$maxid = smtp_maxid();
			$importjs[] = route_admin::site('view/js/smtp.js');
			// hook admin_setting_smtp_get_end.php
			include _include(ADMIN_PATH . "view/htm/setting/smtp.htm");
		else:
			// hook admin_setting_smtp_post_start.php
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
			!$r and message(-1, lang('conf/smtp.conf.php', array('file' => 'conf/smtp.conf.php')));

			// hook admin_setting_smtp_post_end.php

			message(0, lang('save_successfully'));
		endif;
		break;
	case 'time':
		// hook admin_setting_time_get_post.php
		if ($_SERVER['REQUEST_METHOD'] == 'GET'):
			// hook admin_setting_time_get_start.php
			$header['title'] = lang('admin_setting_time');
			// hook admin_setting_time_get_end.php
			include _include(ADMIN_PATH . 'view/htm/setting/time.htm');
		elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
			// hook admin_setting_time_post_start.php
			route_admin::format_post();
			file_replace_var(APP_PATH . 'conf/conf.php', $_POST);
			// hook admin_setting_time_post_end.php
			message(0, lang('modify_successfully'));
		endif;
		break;
	case 'rewrite':
		// hook admin_setting_time_post_start.php
		if ($_SERVER['REQUEST_METHOD'] == 'GET'):
			// hook admin_setting_rewrite_get.php
			$header['title'] = lang('admin_setting_rewrite');
			// hook admin_setting_time_get_end.php
			include _include(ADMIN_PATH . 'view/htm/setting/rewrite.htm');
		elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
			// hook admin_setting_rewrite_post.php
			$_POST['url_rewrite_on'] = MyApp::post('url_rewrite_on',0);
			$_POST['url_rewrite_style'] = MyApp::post('url_rewrite_style',0);
			$_POST['cdn_on'] = MyApp::post('cdn_on',0);
			route_admin::format_post();
			file_replace_var(APP_PATH . 'conf/conf.php', $_POST);
			message(0, lang('modify_successfully'));
		endif;
		break;
	break;
	default:
		// hook admin_setting_base_get_post.php
		if ($_SERVER['REQUEST_METHOD'] == 'GET'):
			// hook admin_setting_base_get_start.php
			$header['title'] = lang('admin_site_setting');
			// hook admin_setting_base_get_end.php
			include _include(ADMIN_PATH . 'view/htm/setting/base.htm');
		elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
			// hook admin_setting_base_post_start.php
			#防止checkbox空值问题
			$_POST['user_create_on'] = MyApp::post('user_create_on',0);
			$_POST['user_create_email_on'] = MyApp::post('user_create_email_on',0);
			$_POST['user_resetpw_on'] = MyApp::post('user_resetpw_on',0);
			$_POST['runlevel'] = MyApp::post('runlevel',0);
			route_admin::format_post();
			file_replace_var(APP_PATH . 'conf/conf.php', $_POST);
			// hook admin_setting_base_post_end.php
			message(0, lang('modify_successfully'));
		endif;
		break;
endswitch;
// hook admin_setting_end.php
