<?php
/**
 * @author N <m@nenge.net>
 * 板块管理
 * 接口  
 * 上传图片 POST DATA: $_FILES['file']  返回结果 json {url:图片地址,icon:时间}
 * 更新 POSTDATA:
 */
!defined('ADMIN_PATH') and exit('Access Denied.');
// hook admin_forumlist.php
if ($_SERVER['REQUEST_METHOD'] == 'GET'):
	// hook admin_forumlist_get_start.php
	$newforumlist = MyDB::t('forum')->whereAll([], MyDB::ORDER(['rank' => 'desc', 'fid' => 'asc']), array('name', 'icon', 'rank', 'fid'));
	$newforumlist = array_column($newforumlist, null, 'fid');
	// hook admin_forumlist_get_end.php
	include(route_admin::tpl_link('forum/home.htm'));
	exit;
elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
	// hook admin_forumlist_post_start.php
	if (!empty(MyApp::head('ajax-fetch'))):
		if (!empty($_FILES['file']) && empty($_FILES['file']['error'])):
			// hook admin_forumlist_post_upload.php
			$imagepath = MyApp::upload_path('forum/' . MyApp::post('fid') . '.png');
			if (move_upload_file($_FILES['file']['tmp_name'], $imagepath)):
				MyApp::message_json(
					array('url' => MyApp::convert_site($imagepath), 'icon' => $_SERVER['REQUEST_TIME'])
				);
			else:
				MyApp::message(-1, '上传失败');
			endif;
		endif;
	endif;
	if (isset($_POST['name']) && is_array($_POST['name'])):
		$datalist = array();
		$keys = array_keys($_POST);
		$forumkeys = MyDB::t('forum')->columns();
		foreach ($_POST['name'] as $k => $v):
			$datalist[$k]['fid'] = intval($k);
			foreach ($keys as $x):
				if (!in_array($x, $forumkeys)) continue;
				$y = $_POST[$x][$k] ?: 0;
				if (is_numeric($y) || !$y):
					$y = intval($y);
				endif;
				$datalist[$k][$x] = $_POST[$x][$k];
			endforeach;
		endforeach;
		// hook admin_forumlist_post_loop_end.php
		if (!empty($datalist)):
			$row = MyDB::t('forum')->insert_map_update($datalist);
			#更新板块缓存
			if ($row > 0):
				forum_list_cache_delete();
				// hook admin_forumlist_post_end.php
				MyApp::message(0, MyApp::Lang('save_successfully'), array('url' => MyApp::purl('forum/list')));
			endif;
		endif;
	endif;
	// hook admin_forumlist_post_end.php
	MyApp::message(0, MyApp::Lang('forum_no_update'));
endif;
exit;
