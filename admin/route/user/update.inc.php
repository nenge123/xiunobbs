<?php

/**
 * @author N <m@nenge.net>
 * 用户
 * 编辑/更新用户
 */
!defined('APP_PATH') and exit('Access Denied.');
$_uid = MyApp::value(1);
if (empty($_uid)):
	MyApp::message(-1, '参数不合法');
endif;
$_user = MyDB::t('user')->whereFirst(['uid' => $_uid]);
if (empty($_user)):
	MyApp::message(-1, '用户不存在');
endif;
// hook admin_user_update_get_post.php
if ($_SERVER['REQUEST_METHOD'] == 'GET'):
	MyApp::setValue('title', MyApp::Lang('user_edit'));
	#把密码设置空白输出
	$_user['password'] = '';
	#密码盐不可随意更改
	unset($_user['salt']);
	include _include(ADMIN_PATH . 'view/htm/user/update.htm');
elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
	route_admin::format_post();
	if (empty($_POST['uid']) || $_POST['uid']<1):
		MyApp::message(-1,MyApp::Lang('uid_not_exists'));
	endif;
	#UID校验
	if (MyApp::post('uid') != $_uid):
		if (MyDB::t('user')->whereCount(['uid' => MyApp::post('uid')])):
			MyApp::message(-1, MyApp::Lang('uid_not_exists'));
		endif;
	endif;
	#用户名校验
	if (MyApp::post('username') != $_user['username']):
		if (empty(MyApp::post('username'))):
			unset($_POST['username']);
		elseif (MyDB::t('user')->whereCount(['username' => MyApp::post('username')])):
			MyApp::message(-1,  MyApp::Lang('user_already_exists'));
		endif;
	endif;
	#邮件校验
	if (MyApp::post('email') != $_user['email']):
		if (empty(MyApp::post('email')) || !filter_var(MyApp::post('email'),FILTER_VALIDATE_EMAIL)):
			unset($_POST['email']);
		elseif (MyDB::t('user')->whereCount(['email' => MyApp::post('email')])):
			MyApp::message(-1,  MyApp::Lang('email_already_exists'));
		endif;
	endif;
	$gids = array_column($grouplist,'gid');
	#不合法gid撤销更改
	if (empty($_POST['gid']) || $_user['uid'] == 1 || !in_array($_POST['gid'],$gids)):
		unset($_POST['gid']);
	endif;
	// hook admin_user_update_post_start.php
	$colums = MyDB::t('user')->columns();
	$update = [];
	$_POST['salt'] = MyApp::post('salt',$_user['salt']);
	foreach ($_POST as $k => $v):
		if (in_array($k, $colums)):
			if($k=='password'):
				#设置密码
				if(empty($v)):
					continue;
				else:
					$v = md5(md5($v) . $_POST['salt']);
				endif;
			elseif (str_ends_with($k, '_date')):
				#时间转为数字值
				$v = strtotime($v);
			elseif (str_ends_with($k, '_ip')):
				if(!filter_var($v,FILTER_VALIDATE_IP)):
					#不合法IP跳过
					continue;
				endif;
				$v = ip2long($v) ?: 0;
			elseif(is_numeric($v)):
				$v = intval($v);
			endif;
			$update[$k] = $v;
		endif;
	endforeach;
	if($_uid===1):
		unset($update['uid']);
	endif;
	// hook admin_user_update_post_end.php
	if (MyDB::t('user')->update_by_where($update, ['uid' => $_uid]) > 0):
		MyApp::message(-1, MyApp::Lang('update_successfully'));
	endif;
	MyApp::message(-1, MyApp::Lang('data_not_changed'));
endif;
exit;