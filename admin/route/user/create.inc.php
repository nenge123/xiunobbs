<?php
/**
 * @author N <m@nenge.net>
 * 用户
 * 批量删除
 */
!defined('ADMIN_PATH') and exit('Access Denied.');
MyApp::setValue('title',MyApp::Lang('admin_user_create'));
if($_SERVER['REQUEST_METHOD']=='POST'):
	$_username = MyApp::post('username');
	$_error = '';
	if(empty($_username) || !is_username($_username,$_error)):
		MyApp::message('username',$_error?:MyApp::Lang('uid_not_exists'));
	endif;
	$_email = MyApp::post('email');
	if(empty($_email)|| !filter_var($_email,FILTER_VALIDATE_EMAIL)):
		MyApp::message('email',MyApp::Lang('email_format_mismatch'));
	endif;
	$_user = MyDB::t('user')->select(...MyDB::WHERE_OR(array('username'=>$_username,'email'=>$_email),'',MyDB::MODE_SINGLE_ASSOC,array('username','email')));
	if(!empty($_user)):
		if( $_user['username'] == $_username ):
			MyApp::message('username',MyApp::Lang('user_already_exists'));
		endif;
		if( $_user['email'] == $_email ):
			MyApp::message('email',MyApp::Lang('email_is_in_use'));
		endif;
	endif;
	if(MyDB::t('user')->whereCount(['username'=>$_username])>0):
	endif;
	$salt = xn_rand(16);
	$_gid = intval(MyApp::post('gid'));
	$uid = user_create(array(
		'username' => $_username,
		'password' => md5(md5(MyApp::post('password')) . $salt),
		'salt' => $salt,
		'gid' => $_gid,
		'email' => $_email,
		'create_ip' => MyApp::data('longip'),
		'create_date' => $_SERVER['REQUEST_TIME']
	));
	//$uid = MyDB::t('user')->insert_json($update);
	if($uid>0):
		MyApp::message(0, MyApp::Lang('create_successfully'),array('url'=>MyApp::purl('update/'.$uid)));
	endif;
	MyApp::message(-1,'添加失败');
endif;
include(route_admin::tpl_link('user/create.htm'));