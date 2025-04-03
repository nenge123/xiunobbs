<?php
/**
 * @author N <m@nenge.net>
 * 用户
 * 封禁 解封
 */
!defined('APP_PATH') and exit('Access Denied.');
if ($_SERVER['REQUEST_METHOD'] == 'POST'):
	#解封/封禁/删除
	$posttype = MyApp::post('action-type');
	if (!empty($posttype)):
		$uids = MyApp::post('uids');
		if (empty($uids)):
			MyApp::message(-1, '请先勾选');
		endif;
		#原则上不能封禁管理员
		$_userlist = route_admin::safe_uids($uids);
		if (empty($_userlist)):
			MyApp::message(-1,MyApp::Lang('user_admin_cant_be_deleted'));
		endif;
		switch ($posttype):
			case 'block':
				$row = MyDB::t('user')->update_by_where(array('gid' => 7), ['uid' => $_userlist]);
				if ($row > 0):
					MyApp::message(0, MyApp::Lang('ban_user').':'.$row, array('uids' => $_userlist));
				else:
					MyApp::message(-1, MyApp::Lang('data_not_changed'));
				endif;
			case 'unblock':
				$length = [];
				foreach( $_userlist as $v):
					if(\model\group::update($v) === TRUE):
						$length[] = $v;
					endif;
				endforeach;
				if(count($length)>0):
					MyApp::message(0, MyApp::Lang('unban_user').':'.count($length), array('uids' => $length));
				else:
					MyApp::message(-1, MyApp::Lang('data_not_changed'));
				endif;
			break;
		endswitch;
	endif;
endif;
MyApp::message(-1, '请先勾选');