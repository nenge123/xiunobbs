<?php

!defined('APP_PATH') and exit('Access Denied.');

$action = MyApp::value(0);

// hook admin_user_start.php
switch ($action):
	case 'create':

		// hook admin_user_create_get_post.php

		if ($_SERVER['REQUEST_METHOD'] == 'GET') {

			// hook admin_user_create_get_start.php

			$header['title'] = lang('admin_user_create');
			$header['mobile_title'] = lang('admin_user_create');

			$input['email'] = form_text('email', '');
			$input['username'] = form_text('username', '');
			$input['password'] = form_password('password', '');
			$grouparr = arrlist_key_values($grouplist, 'gid', 'name');
			$input['_gid'] = form_select('_gid', $grouparr, 0);

			// hook admin_user_create_get_end.php

			include _include(ADMIN_PATH . "view/htm/user_create.htm");
		} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {

			$email = param('email');
			$username = param('username');
			$password = param('password');
			$_gid = param('_gid');

			// hook admin_user_create_post_start.php

			empty($email) and message('email', lang('please_input_email'));
			$email and !is_email($email, $err) and message('email', $err);
			$username and !is_username($username, $err) and message('username', $err);

			$_user = user_read_by_email($email);
			$_user and message('email', lang('email_is_in_use'));

			$_user = user_read_by_username($username);
			$_user and message('username', lang('user_already_exists'));

			$salt = xn_rand(16);
			$r = user_create(array(
				'username' => $username,
				'password' => md5(md5($password) . $salt),
				'salt' => $salt,
				'gid' => $_gid,
				'email' => $email,
				'create_ip' => ip2long(ip()),
				'create_date' => $_SERVER['REQUEST_TIME']
			));
			$r === FALSE and message(-1, lang('create_failed'));

			// hook admin_user_create_post_end.php

			message(0, lang('create_successfully'));
		}
		break;
	case 'update':
		$_uid = param(2, 0);

		// hook admin_user_update_get_post.php

		if ($_SERVER['REQUEST_METHOD'] == 'GET') {

			// hook admin_user_update_get_start.php

			$header['title'] = lang('user_edit');
			$header['mobile_title'] = lang('user_edit');

			$_user = user_read($_uid);

			$input['email'] = form_text('email', $_user['email']);
			$input['username'] = form_text('username', $_user['username']);
			$input['password'] = form_password('password', '');
			$grouparr = arrlist_key_values($grouplist, 'gid', 'name');
			$input['_gid'] = form_select('_gid', $grouparr, $_user['gid']);

			// hook admin_user_update_get_end.php

			include _include(ADMIN_PATH . "view/htm/user_update.htm");
		} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {

			$email = param('email');
			$username = param('username');
			$password = param('password');
			$_gid = param('_gid');

			// hook admin_user_update_post_start.php

			$old = user_read($_uid);
			empty($old) and message('username', lang('uid_not_exists'));

			$email and !is_email($email, $err) and message(2, $err);
			if ($email and $old['email'] != $email) {
				$_user = user_read_by_email($email);
				$_user and $_user['uid'] != $_uid and message('email', lang('email_already_exists'));
			}
			if ($username and $old['username'] != $username) {
				$_user = user_read_by_username($username);
				$_user and $_user['uid'] != $_uid and message('username', lang('user_already_exists'));
			}

			$arr = array();
			$arr['email'] = $email;
			$arr['username'] = $username;
			$arr['gid'] = $_gid;

			if ($password) {
				$salt = xn_rand(16);
				$arr['password'] = md5(md5($password) . $salt);
				$arr['salt'] = $salt;
			}

			// hook admin_user_update_post_exec_before.php

			// 仅仅更新发生变化的部分 / only update changed field
			$update = array_diff_value($arr, $old);
			empty($update) and message(-1, lang('data_not_changed'));

			$r = user_update($_uid, $update);
			$r === FALSE and message(-1, lang('update_failed'));

			// hook admin_user_update_post_end.php

			message(0, lang('update_successfully'));
		}
		break;
	case 'delete':
		if ($_SERVER['REQUEST_METHOD'] != 'POST') message(-1, 'Method Error.');

		$_uid = param('uid', 0);

		// hook admin_user_delete_start.php

		$_user = user_read($_uid);
		empty($_user) and message(-1, lang('user_not_exists'));
		($_user['gid'] == 1) and message(-1, 'admin_cant_be_deleted');

		$r = user_delete($_uid);
		$r === FALSE and message(-1, lang('delete_failed'));

		// hook admin_user_delete_end.php

		message(0, lang('delete_successfully'));
		break;
	default:
		$header['title'] = lang('user_admin');
		$header['mobile_title'] = lang('user_admin');
		$pagesize = 20;
		$size = 0;
		if($_SERVER['REQUEST_METHOD']=='POST'):
			$page     = intval(MyApp::post('page',1));
			$where = array();
			if(MyApp::post('uid')):
				$size = 1;
				$userlist = MyDB::t('user')->where(['uid'=>MyApp::post('uid')],'',10);
			else:
				if(MyApp::post('gid')):
					$where['gid'] = intval(MyApp::post('gid'));
				endif;
				if(MyApp::post('email')):
					$where['%email'] = MyApp::post('email');
				endif;
				if(MyApp::post('username')):
					$where['%username'] = MyApp::post('username');
				endif;
				if(MyApp::post('create_ip')):
					$_ip = MyApp::post('create_ip');
					if(str_contains($_ip,'::')):
						//ipv6
						MyApp::message(-1,'程序目前不支持IPV6');
					elseif(!preg_match('/\d+\.\d+\.\d+\.\d+/',$_ip)):
						$where['create_ip'] =ip2long($_ip);
					else:
						MyApp::message(-1,'程序目前不支持IPV4 范围搜索');
					endif;
				endif;
				$size = MyDB::t('user')->whereCount($where);
				$userlist = MyDB::t('user')->where(
					$where,
					MyDB::ORDER(['uid'=>'asc']).
					MyDB::LIMIT($page,$pagesize),
					MyDB::MODE_ITERATOR
				);
			endif;
			if($size>0):				
				$maxpage = ceil($size / $pagesize);
				$pagination = MyApp::pagination($maxpage, $page);
			endif;
		endif;
/*
		// hook admin_user_list_start.php

		$cond = array();
		$allowtype = array('uid', 'username', 'email', 'gid', 'create_ip');

		// hook admin_user_list_allow_type_after.php

		if ($keyword) {
			!in_array($srchtype, $allowtype) and $srchtype = 'uid';
			$cond[$srchtype] = $srchtype == 'create_ip' ? ip2long($keyword) : $keyword;
		}

		// hook admin_user_list_cond_after.php
		$n = user_count($cond);
		$userlist = user_find($cond, array('uid' => -1), $page, $pagesize);
		$pagination = pagination(url("user-list-$srchtype-" . urlencode($keyword) . '-{page}'), $n, $page, $pagesize);
		$pager = pager(url("user-list-$srchtype-" . urlencode($keyword) . '-{page}'), $n, $page, $pagesize);

		foreach ($userlist as &$_user) {
			$_user['group'] = array_value($grouplist, $_user['gid'], '');
		}
*/
		// hook admin_user_list_end.php
		$importjs[] = route_admin::site('view/js/user_methods.js');
		include _include(ADMIN_PATH . "view/htm/user/list.htm");
		break;
endswitch;
// hook admin_user_start.php
