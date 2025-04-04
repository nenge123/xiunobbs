<?php
/**
 * @author N <m@nenge.net>
 * 主题批量删除/关闭/封禁
 * 接口  
 * 批量删除 new EventSource(url+'?tids=1|2|4'); 涉及事件 open/progress/close 数据类型json
 * 批量关闭/打开/封禁/解封POST:url,DATA:{'action-type':'操作标识',tids:[]} 返回结果json
 * 默认接口 POST:url,DATA:any 返回结果json:{list:主题列表,pagelist:分页列表,page:页码,maxpage:最大页面,total:结果数量}
 */
!defined('ADMIN_PATH') and exit('Access Denied.');
// hook admin_threaddelete_start.php
if ($_SERVER['REQUEST_METHOD'] == 'POST'):
	// hook admin_threaddelete_post_any_start.php
	$posttype = MyApp::post('action-type');
	if (!empty($posttype)):
		#批量操作 关闭/打开主题 封禁/解封主题
		switch ($posttype):
			case 'closed':
			case 'open':
				#关闭/打开
				$tids = MyApp::post('tids');
				if (!empty($tids)):
					// hook admin_threaddelete_open_and_close.php
					$rows = MyDB::t('thread')->update_by_where(['closed' => $posttype == 'closed' ? 1 : 0], ['tid' => $tids]);
					if ($rows > 0):
						if ($posttype == 'closed'):
							MyApp::message(0, '你已关闭了' . $rows . '条主题');
						else:
							MyApp::message(0, '你已重新打开了' . $rows . '条主题');
						endif;
					endif;
				endif;
				MyApp::message(-1, MyApp::Lang('data_not_changed'));
				break;
			case 'block':
			case 'unblock':
				$tids = MyApp::post('tids');
				if (!empty($tids)):
					// hook admin_threaddelete_block.php
					MyApp::message(-1, '你没安装封禁主题,回收站功能插件!');
				endif;
				MyApp::message(-1, MyApp::Lang('data_not_changed'));
				break;
		endswitch;
	endif;
elseif ($_SERVER['REQUEST_METHOD'] == 'GET'):
	if (MyApp::head('accept') == 'text/event-stream'):
		#报文文件头
		#IIS支持不太友好,但是问题不大 详情看帮助里的event-stream
		route_admin::eventStart();
		// hook admin_threaddelete_eventstream_start.php
		if (isset($_GET['tids'])):
			#批量删除主题接口
			$id = 1;
			if (empty($tids)):
				self::eventMessage('close', $id, array('message' => '没有勾选主题!!', 'url' => MyApp::purl('forum/list')));
				exit;
			endif;
			$tids = explode('|', $tids);
			$haslist = false;
			// hook admin_threaddelete_eventstream_getlist.php
			if(empty($threadlist)):
				#此处用了迭代器结果集
				$threadlist = MyDB::t('thread')->where(['tid' => $tids], '', MyDB::MODE_ITERATOR, array('uid', 'tid', 'subject'));
				$haslist = $threadlist->valid();
			endif;
			if ($haslist):
				#存在主题 先删除主题
				self::eventMessage('progress', $id, array('message' => sprintf(MyApp::Lang('forum_event_stream_progress'), count($tids))));
				$id++;
				sleep(1);
				foreach ($threadlist as $thread):
					self::eventMessage('progress', $id, array('message' => sprintf(MyApp::Lang('forum_event_stream_progress_subject'), $thread['subject'])));
					$id++;
					thread_delete($thread['tid']);
					self::eventMessage('progress', $id, array('tid' => $thread['tid']));
					$id++;
				endforeach;
				self::eventMessage('progress', $id, array('message' => sprintf('本次删除%s条主题成功!', count($tids))));
				$id++;
				sleep(1);
			endif;
			// hook admin_threaddelete_eventstream_success.php
			self::eventMessage('close', $id, array('message' => '勾选的主题删除完毕!!!'));
			exit;
		endif;
		// hook admin_threaddelete_eventstream_end.php
		route_admin::eventMessage('close', 1, ['message' => '未知操作']);
		exit;
	endif;
endif;
$total = 0;
$limit = 30;
if ($_SERVER['REQUEST_METHOD'] == 'POST'):
	#提交数据搜索
	// hook admin_threaddelete_post_start.php
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
	// hook admin_threaddelete_query.php
	$total = MyDB::t('thread')->whereCount($where);
	$columns = array('tid', 'subject', 'closed');
	$datalist = array(
		'page' => $page,
		'limit' => $limit,
		'total' => $total,
		'maxpage' => ceil($total / $limit),
		'list' => MyDB::t('thread')->where($where, MyDB::ORDER(['tid' => 'asc']) . MyDB::LIMIT($page, $limit), MyDB::MODE_ALL_ASSOC, $columns),
	);
	$datalist['pagelist'] = MyApp::pagination($datalist['maxpage'], $page);
	// hook admin_threaddelete_post_end.php
	MyApp::message_json($datalist);
elseif ($_SERVER['REQUEST_METHOD'] == 'GET'):
	#模板输出
	// hook admin_threaddelete_get_start.php
	$threadlist = array();
	// hook admin_threaddelete_get_end.php
	include(route_admin::tpl_link('forum/threaddelete.htm'));
endif;
