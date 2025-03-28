<?php
!defined('APP_PATH') and exit('Access Denied.');
$action = MyApp::value(0);
// hook admin_thread_start.php
$pagesize = 100;
switch ($action):
	case 'delete':
		$_tid = intval(MyApp::value(1));
		$_thread = MyDB::t('thread')->whereFirst(['tid' => $_tid], '', array('tid', 'subject', 'closed'));
		if ($_SERVER['REQUEST_METHOD'] == 'POST'):
			switch (MyApp::post('action')):
				case '1':
					// hook admin_thread_delete_close.php
					if (empty($_thread['closed']) && MyDB::t('thread')->update_by_where(['closed' => 1], ['tid' => $_tid]) > 0):
						MyApp::message(0, '主题关闭成功,主题不可回复了');
					elseif (!empty($_thread['closed']) && MyDB::t('thread')->update_by_where(['closed' => 0], ['tid' => $_tid]) > 0):
						MyApp::message(0, '主题重新打开成功,主题可回复了');
					endif;
					break;
				case '2':
					// hook admin_thread_delete_block.php
					MyApp::message(-1, '你安装封禁主题,回收站功能插件!');
					break;
				case '3':
					// hook admin_thread_delete_remove.php
					if (thread_delete($_tid)):
						MyApp::message(0, '删除成功!!', ['url' => MyApp::purl('list')]);
					endif;
					break;
			endswitch;
			MyApp::message(-1, '没变化');
		endif;
		include _include(ADMIN_PATH . "view/htm/thread/delete.htm");
		break;
	default:
		if (MyApp::head('accept') == 'text/event-stream'):
			#每次删除100条主题
			route_admin::thread_delete_list();
		endif;
		$header['title'] = lang('thread_admin');
		$header['mobile_title'] = lang('thread_admin');
		$threadlist = array();
		$maxlength = 0;
		// hook admin_thread_list_start.php
		if ($_SERVER['REQUEST_METHOD'] == 'POST'):
			$where = array();
			$limit = 30;
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
			$threadlist = MyDB::t('thread')->where($where, MyDB::ORDER(['tid' => 'asc']) . MyDB::LIMIT($page, $limit), MyDB::MODE_ITERATOR, array('tid', 'subject'));
			$maxpage = ceil($maxlength / $limit);
			$pagination = MyApp::pagination($maxpage, $page);
		endif;
		// hook admin_thread_list_end.php
		include _include(ADMIN_PATH . "view/htm/thread/list.htm");
		break;
endswitch;
// hook admin_thread_end.php