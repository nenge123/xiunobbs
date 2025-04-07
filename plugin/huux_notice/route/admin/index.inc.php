<?php

/**
 * @author N <m@nenge.net>
 * 短消息群发
 */
!defined('ADMIN_PATH') and exit('Access Denied.');
if($_SERVER['REQUEST_METHOD'] == 'GET'):
	$input = array();
	$input['recvuid'] = form_text('recvuid', '');
	$input['message'] = form_textarea('message', '', '100%', 100);
	MyApp::setValue('title', MyApp::Lang('notice_admin_send_notice'));
	include huux_notice::tpl_link('admin/home.htm');
	exit;
elseif($_SERVER['REQUEST_METHOD'] == 'POST'):
	$message = MyApp::post('message', '');
	$recvuid = MyApp::post('recvuid', 0);
	// 检查内容和接收人是否为空
	empty($message) AND MyApp::message('message', MyApp::Lang('notice_admin_send_notice_message_empty'));
	empty($recvuid) AND MyApp::message('recvuid', MyApp::Lang('notice_admin_send_notice_recvuid_empty'));

	// 检查接收人是否存在
	if(MyDB::t('user')->whereCount(['uid'=>$recvuid])==0):
		MyApp::message('recvuid', MyApp::Lang('notice_admin_send_notice_user_empty'));
	endif;
	$nid = huux_notice::notice_send($uid, $recvuid, $message, 3); // 3:系统通知
	$nid === FALSE AND MyApp::message(-1, MyApp::Lang('notice_admin_send_notice_failed'));
	MyApp::message(0, MyApp::Lang('notice_admin_send_notice_sucessfully'));
endif;