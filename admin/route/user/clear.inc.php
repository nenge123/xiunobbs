<?php
/**
 * @author N <m@nenge.net>
 * 用户
 * 批量删除
 */
!defined('APP_PATH') and exit('Access Denied.');
if ($_SERVER['REQUEST_METHOD'] == 'GET'):
	if (MyApp::head('accept') == 'text/event-stream'):
		#报文文件头
		#IIS支持不太友好,但是问题不大 详情看帮助里的event-stream
		route_admin::eventStart();
		$uids = trim(MyApp::param('uids'));
		if (empty($uids)):
			route_admin::eventMessage('close', 1, ['message' => '请先勾选']);
		endif;
		$uids = explode('|',$uids);
		$_userlist = route_admin::safe_uids($uids);
		if (empty($_userlist)):
			#原则上不能封禁管理员 
			route_admin::eventMessage('close', 1, ['message' => lang('user_admin_cant_be_deleted')]);
		endif;
		route_admin::eventMessage('close', 1, ['message' => '未知操作']);
		exit;
	endif;
endif;
MyApp::message(-1, '请先勾选');