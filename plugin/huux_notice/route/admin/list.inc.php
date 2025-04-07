<?php

/**
 * @author N <m@nenge.net>
 * 短消息列表
 */

use model\tpl;
!defined('ADMIN_PATH') and exit('Access Denied.');
if($_SERVER['REQUEST_METHOD']=='POST'):
	$nid = MyApp::post('nid');
	if(huux_notice::notice_delete($nid)<1):
		MyApp::message(-1, MyApp::Lang('notice_delete_notice_failed'));
	endif;
	MyApp::message(0,MyApp::Lang('notice_delete_notice_sucessfully'),['method'=>'remove_pm']);
	exit;
endif;
$page = intval(MyApp::value(2,1))?:1;
$where = array();
$pagesize = 20;
$_mode = MyApp::value(3);
$_url = $plugin_dir.'/list/%s';
$_user_only_mode = '';
switch($_mode):
	case 'read':
		$where['isread'] = 1;
		$_url .= '/read';
		$_user_only_mode = 'read';
	break;
	case 'unread':
		$where['isread'] = 0;
		$_url .= '/unread';
		$_user_only_mode = 'unread';
	break;
	case 'fromuid':
	case 'recvuid':
		$_uid = MyApp::value(4);
		if(is_numeric($_uid)):
			$where[$_mode] = $_uid;
			$_url .= '/'.$_mode.'/'.$_uid;
			$_submode = MyApp::value(5);
			if(!empty($_submode)):
				if($_submode == 'read'):
					$where['isread'] = 1;
					$_url .= '/read';
					$_user_only_mode = 'read';
				elseif($_submode == 'unread'):
					$where['isread'] = 0;
					$_url .= '/unread';
					$_user_only_mode = 'unread';
				endif;
			endif;
		endif;
	break;
endswitch;
$total = huux_notice::count($where);
$maxpage = ceil($total/$pagesize);
$_user_only = $plugin_dir.'/list/1/%s/%d/'.$_user_only_mode;
$noticelist = huux_notice::list($where,array('nid'=>'desc'),$page, $pagesize);
$pagination = tpl::pagination($_url, $maxpage,$page);
MyApp::setValue('title',MyApp::Lang('notice_admin_notice_list'));
include huux_notice::tpl_link('admin/list.htm');
