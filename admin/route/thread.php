<?php
!defined('APP_PATH') and exit('Access Denied.');
$action = MyApp::value(0);
// hook admin_thread_start.php

if ($_SERVER['REQUEST_METHOD'] == 'POST'):
	#批量操作 关闭/打开主题 封禁/解封主题
	$actiontype = MyApp::post('action-type');
	if (!empty($actiontype)):
		switch ($actiontype):
			case 'closed':
			case 'open':
				$tids = MyApp::post('tids');
				if (!empty($tids)):
					// hook admin_thread_open_and_close.php
					$rows = MyDB::t('thread')->update_by_where(['closed' => $actiontype == 'closed' ? 1 : 0], ['tid' => $tids]);
					if ($rows):
						if ($actiontype == 'closed'):
							MyApp::message(0, '你已关闭了' . $rows . '条主题');
						else:
							MyApp::message(0, '你已重新打开了' . $rows . '条主题');
						endif;
					endif;
				endif;
				MyApp::message(-1, '没变化');
				break;
			case 'block':
			case 'unblock':
				$tids = MyApp::post('tids');
				if (!empty($tids)):
					// hook admin_thread_block.php
					MyApp::message(-1, '你没安装封禁主题,回收站功能插件!');
				endif;
				MyApp::message(-1, '没变化');
				break;
		endswitch;
	endif;
endif;
if (MyApp::head('accept') == 'text/event-stream'):
	#批量删除接口
	route_admin::thread_delete_list();
endif;
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
	$datalist = array(
		'page' => $page,
		'limit' => $limit,
		'maxlen' => $maxlength,
		'maxpage' => ceil($maxlength / $limit),
		'list' => MyDB::t('thread')->where($where, MyDB::ORDER(['tid' => 'asc']) . MyDB::LIMIT($page, $limit), MyDB::MODE_ALL_ASSOC, $columns),
	);
	$datalist['pagelist'] = MyApp::pagination($datalist['maxpage'], $page);
	// hook admin_thread_list_post_end.php
	MyApp::message_json($datalist);
endif;
// hook admin_thread_list_end.php
include _include(ADMIN_PATH . "view/htm/thread/list.htm");
