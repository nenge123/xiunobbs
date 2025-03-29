<?php
!defined('APP_PATH') and exit('Access Denied.');
$action = MyApp::value(0);
// hook admin_thread_start.php
switch ($action):
	case 'delete':
		if (MyApp::head('accept') == 'text/event-stream'):
			route_admin::thread_delete_list();
		endif;
		break;
	case 'closed':
		$tids = MyApp::post('tids');
		if (!empty($tids)):
			// hook admin_thread_closed.php
			$rows = MyDB::t('thread')->update_by_where(['closed' => 1], ['tid' => $tids]);
			if ($rows):
				MyApp::message(0, '你已关闭了' . $rows . '条主题');
			endif;
		endif;
		MyApp::message(-1, '没变化');
		break;
	case 'open':
		$tids = MyApp::post('tids');
		if (!empty($tids)):
			// hook admin_thread_open.php
			$rows = MyDB::t('thread')->update_by_where(['closed' => 0], ['tid' => $tids]);
			if ($rows):
				MyApp::message(0, '你已重新打开了' . $rows . '条主题');
			endif;
		endif;
		MyApp::message(-1, '没变化');
		break;
	case 'block':
		$tids = MyApp::post('tids');
		if (!empty($tids)):
			// hook admin_thread_block.php
			MyApp::message(-1, '你没安装封禁主题,回收站功能插件!');
		endif;
		MyApp::message(-1, '没变化');
		break;
	case 'unblock':
		$tids = MyApp::post('tids');
		if (!empty($tids)):
			// hook admin_thread_unblock.php
			MyApp::message(-1, '你没安装封禁主题,回收站功能插件!');
		endif;
		MyApp::message(-1, '没变化');
		break;
	default:
		// hook admin_thread_end.php
		$header['title'] = lang('thread_admin');
		$header['mobile_title'] = lang('thread_admin');
		$threadlist = array();
		$maxlength = 0;
		$limit = 30;
		// hook admin_thread_list_start.php
		if ($_SERVER['REQUEST_METHOD'] == 'POST'):
			// hook admin_thread_list_post_start.php
			$where = array();
			$fid = intval(MyApp::post('fid'));
			$page = intval(MyApp::post('page', 1));
			$keyword = MyApp::post('keyword');
			$create_date_start = MyApp::post('create_date_start');
			$create_date_end = MyApp::post('create_date_end');
			$userip = MyApp::post('userip');
			$uid = intval(MyApp::post('uid'));
			if (!empty($create_date_start)):
				$where['>create_date'] = strtotime($create_date_start);
			endif;
			if (!empty($create_date_end)):
				$where['<create_date'] = strtotime($create_date_end);
			endif;
			if (!empty($fid)):
				$where['fid'] = $fid;
			endif;
			if (!empty($keyword)):
				$where['%subject'] = $keyword;
			endif;
			if (!empty($userip)):
				$where['%'] = strtotime($userip);
			endif;
			if (!empty($uid)):
				$where['uid'] = $uid;
			endif;
			$maxlength = MyDB::t('thread')->whereCount($where);
			$columns = array('tid', 'subject', 'closed');
			// hook admin_thread_list_query.php
			$threadlist = MyDB::t('thread')->where($where, MyDB::ORDER(['tid' => 'asc']) . MyDB::LIMIT($page, $limit), MyDB::MODE_ITERATOR, $columns);
			$maxpage = ceil($maxlength / $limit);
			$pagination = MyApp::pagination($maxpage, $page);
		endif;
		// hook admin_thread_list_end.php
		$importjs[] = route_admin::site('view/js/thread-list.js');
		include _include(ADMIN_PATH . "view/htm/thread/list.htm");
		break;
endswitch;
