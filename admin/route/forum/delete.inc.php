<?php
/**
 * @author N <m@nenge.net>
 * 板块删除
 * 接口  
 * GET text/event-stream
 */
!defined('APP_PATH') and exit('Access Denied.');
$_fid = intval(MyApp::value(1));
$_forum = MyDB::t('forum')->whereFirst(['fid' => $_fid], '', array('name', 'fid'));
if (MyApp::head('accept') == 'text/event-stream'):
	#每次删除100条主题
	route_admin::eventStart();
	// hook admin_forumdelete_eventstream_start.php
	$id = 1;
	if (empty($_forum)):
		#板块不存在
		route_admin::eventMessage('close', $id, array('message' => lang('forum_not_exists'), 'url' => MyApp::purl('forum/index')));
		exit;
	endif;
	if (MyDB::t('forum')->selectCount() == 1):
		#只有一个板块不允许删除
		route_admin::eventMessage('close', $id, array('message' => lang('forum_cant_delete_system_reserved'), 'url' => MyApp::purl('forum/index')));
		exit;
	endif;
	$_fid = $_forum['fid'];
	if (isset($_forum['fup'])):
		#不能含有删除子版块 (程序本体默认不支持子版块)
		foreach ($GLOBALS['forumlist'] as $k => $v):
			if ($v['fup'] == $_fid):
				route_admin::eventMessage('close', $id, array('message' => lang('forum_please_delete_sub_forum'), 'url' => MyApp::purl('forum/index')));
				exit;
			endif;
		endforeach;
	endif;
	#开始
	route_admin::eventMessage('open', $id, array('message' => lang('forum_event_stream_start')));
	$id++;
	$haslist = false;
	// hook admin_forumdelete_eventstream_getlist.php
	if(empty($haslist) || empty($threadlist)):
		$threadlist = MyDB::t('thread')->where(['fid' => $_fid], MyDB::LIMIT($size), MyDB::MODE_ITERATOR, array('uid', 'tid', 'subject'));
		$haslist = $threadlist->valid();
	endif;
	if ($haslist):
		route_admin::eventMessage('progress', $id, array('message' => sprintf(lang('forum_event_stream_progress'), $size)));
		$id++;
		#存在主题 先删除主题
		sleep(1);
		foreach ($threadlist as $thread):
			route_admin::eventMessage('progress', $id, array('message' => sprintf(lang('forum_event_stream_progress_subject'), $thread['subject'])));
			$id++;
			thread_delete($thread['tid']);
		endforeach;
		route_admin::eventMessage('progress', $id, array('message' => sprintf(lang('forum_event_stream_progress_success'), $size)));
		$id++;
		exit;
	endif;
	MyDB::t('forum')->delete_by_where(['fid' => $_fid]);
	MyDB::t('forum_access')->delete_by_where(['fid' => $_fid]);
	forum_list_cache_delete();
	// hook admin_forumdelete_eventstream_end.php
	route_admin::eventMessage('close', $id, array('message' => lang('forum_event_stream_close'), 'url' => MyApp::purl('forum/index')));
	exit;
endif;
if ($_SERVER['REQUEST_METHOD'] == 'GET'):
	empty($_forum) and MyApp::message(-1, lang('forum_not_exists'));
	// hook admin_forumdelete_get_start.php
	MyApp::setValue('title',lang('forum_delete').':'.$_forum['name']);
	include _include(ADMIN_PATH . "view/htm/forum/delete.htm");
	exit;
elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
	MyApp::message(-1, lang('error_request'));
endif;
