<?php
!defined('APP_PATH') and exit('Access Denied.');
$action = MyApp::value(0);
if (empty(MyApp::cookies('admin_token'))):
	$action = 'login';
endif;
// hook admin_index_start.php
switch ($action):
	case 'logout':
		// hook admin_index_logout_start.php
		admin_token_clean();
		message(0, jump(lang('logout_successfully'), MyApp::url('index')));
		break;
	case 'login':
		// hook admin_index_login_get_post.php
		$header['title'] = lang('admin_login');
		if ($_SERVER['REQUEST_METHOD'] == 'GET'):
			// hook admin_index_login_get_start.php
			include _include(ADMIN_PATH . "view/htm/index_login.htm");
		elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
			// hook admin_index_login_post_start.php
			$password = MyApp::post('password');
			if (md5($password . $user['salt']) != $user['password']):
				xn_log('password error. uid:' . $user['uid'] . ' - ******' . substr($password, -6), 'admin_login_error');
				#密码错误
				MyApp::message('password', lang('password_incorrect'));
			endif;
			admin_token_set();
			xn_log('login successed. uid:' . $user['uid'], 'admin_login');
			// hook admin_index_login_post_end.php
			MyApp::message(0, lang('login_successfully'), ['url' => MyApp::url('index')]);
		endif;
		break;
	case 'phpinfo':
		unset($_SERVER['conf']);
		ob_start();
		phpinfo();
		$data = ob_get_clean();
		include _include(ADMIN_PATH . "view/htm/new-header.htm");
		if (preg_match('/\<body[^>]*\>(.+?)\<\/body\>/is', $data, $matches)):
			echo $matches[1];
			echo '<style type="text/css">
.lyear-layout-content .container-fluid {background-color: #fff; color: #222; font-family: sans-serif;}
.lyear-layout-content .container-fluid pre {margin: 0; font-family: monospace;}
.lyear-layout-content .container-fluid a:link {color: #009; text-decoration: none; background-color: #fff;}
.lyear-layout-content .container-fluid a:hover {text-decoration: underline;}
.lyear-layout-content .container-fluid table {border-collapse: collapse; border: 0; width: 100%; box-shadow: 1px 2px 3px #ccc;}
.lyear-layout-content .container-fluid .center {text-align: center;}
.lyear-layout-content .container-fluid .center table {margin: 1em auto; text-align: left;}
.lyear-layout-content .container-fluid .center th {text-align: center !important;}
.lyear-layout-content .container-fluid td,
.lyear-layout-content .container-fluid th {border: 1px solid #666; font-size: 75%; vertical-align: baseline; padding: 4px 5px;}
.lyear-layout-content .container-fluid th {position: sticky; top: 0; background: inherit;}
.lyear-layout-content .container-fluid h1 {font-size: 150%;}
.lyear-layout-content .container-fluid h2 {font-size: 125%;}
.lyear-layout-content .container-fluid .p {text-align: left;}
.lyear-layout-content .container-fluid .e {background-color: #ccf; width: 300px; font-weight: bold;}
.lyear-layout-content .container-fluid .h {background-color: #99c; font-weight: bold;}
.lyear-layout-content .container-fluid .v {background-color: #ddd; max-width: 300px; overflow-x: auto; word-wrap: break-word;}
.lyear-layout-content .container-fluid .v i {color: #999;}
.lyear-layout-content .container-fluid hr {width: 934px; background-color: #ccc; border: 0; height: 1px;}
.lyear-layout-content .container-fluid pre {background-color: transparent;color: currentColor;}
</style>';
		endif;
		include _include(ADMIN_PATH . "view/htm/new-footer.htm");
		exit;
		break;
	default:
		// hook admin_index_empty_start.php
		$header['title'] = lang('admin_page');
		$info = array();
		$info['disable_functions'] = ini_get('disable_functions');
		$info['allow_url_fopen'] = ini_get('allow_url_fopen') ? lang('yes') : lang('no');
		$info['safe_mode'] = ini_get('safe_mode') ? lang('yes') : lang('no');
		empty($info['disable_functions']) && $info['disable_functions'] = lang('none');
		$info['upload_max_filesize'] = ini_get('upload_max_filesize');
		$info['post_max_size'] = ini_get('post_max_size');
		$info['memory_limit'] = ini_get('memory_limit');
		$info['max_execution_time'] = ini_get('max_execution_time');
		$info['dbversion'] = $db->version();
		$info['SERVER_SOFTWARE'] = $_SERVER['SERVER_SOFTWARE'] ?? '';
		$info['HTTP_X_FORWARDED_FOR'] = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
		$info['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '';
		$stat = array();
		$stat['threads'] = thread_count();
		$stat['posts'] = post_count();
		$stat['users'] = user_count();
		$stat['attachs'] = attach_count();
		$stat['disk_free_space'] = function_exists('disk_free_space') ? humansize(disk_free_space(APP_PATH)) : lang('unknown');
		// hook admin_index_empty_end.php
		include _include(ADMIN_PATH . 'view/htm/index.htm');
		break;
endswitch;
// hook admin_index_end.php
