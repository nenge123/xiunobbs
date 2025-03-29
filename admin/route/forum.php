<?php
!defined('APP_PATH') and exit('Access Denied.');
$action = MyApp::value(0);
// hook admin_forum_start.php
switch ($action):
	case 'update':
		$_fid = MyApp::value(1);;
		$_forum = MyDB::t('forum')->whereFirst(['fid' => $_fid]);
		empty($_forum) and message(-1, lang('forum_not_exists'));
		if ($_SERVER['REQUEST_METHOD'] == 'GET'):
			//user_ids_to_names($_forum['moduids'])
			$header['title']        = lang('forum_edit');
			$header['mobile_title'] = lang('forum_edit');
			// hook admin_forum_update_get_start.php
			$accesslist = MyDB::t('forum_access')->whereAll(array('fid' => $_fid), MyDB::ORDER(['gid' => 'asc']));
			if (empty($accesslist)):
				foreach ($grouplist as $k => $v):
					$accesslist[$k] = $v; // 字段名相同，直接覆盖。 / same field, directly overwrite
				endforeach;
			else:
				$accesslist = array_column($accesslist, null, 'gid');
				foreach ($grouplist as $k => $v):
					$accesslist[$k] = array_merge($grouplist[$k],$accesslist[$k] ?? array());
				endforeach;
			endif;
			if (isset($accesslist[7])):
				#禁用对禁止用户设置
				unset($accesslist[7]);
			endif;
			if (empty($_forum['moduids'])):
				$_forum['modnames'] = '';
			else:
				$_forum['modnames'] = implode(',', array_column(MyDB::t('user')->where(['uid' => explode(',', $_forum['moduids'])], MyDB::ORDER(['uid' => 'asc']), 2, array('username')), 0));
			endif;
			// hook admin_forum_update_get_end.php
			$importjs[] = route_admin::site('view/js/forum-update.js');
			include _include(ADMIN_PATH . "view/htm/forum/update.htm");
			exit;
		elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
			// hook admin_forum_update_post_start.php
			#获取论坛板块的字段
			$forumkeys = MyDB::t('forum')->columns();
			if (!empty(MyApp::head('ajax-fetch'))):
				new model\adminupload($_fid);
			endif;
			if (count($_POST) == 1):
				#单独更新一个字段
				$key = array_keys($_POST)[0];
				if (in_array($key, $forumkeys)):
					// hook admin_forum_update_post_a_key.php
					MyDB::t('forum')->update_by_where($_POST, array('fid' => $_forum['fid']));
					$ajax = 1;
					$_SERVER['ajax'] = 1;
					message(0, lang('forum_' . $key) . lang('admin_forum_save_brief'));
				endif;
			endif;
			$update = array('fid' => $_fid);
			$allowlist = [];
			$rows = 0;
			$_POST['accesson'] = MyApp::post('accesson',0);
			foreach ($_POST as $k => $v):
				if (in_array($k, $forumkeys)):
					$update[$k] = $v;
				elseif (str_starts_with($k, 'allow')):
					foreach ($v as $x => $y):
						$allowlist[$x][$k] = intval($y);
					endforeach;
				endif;
			endforeach;
			// hook admin_forum_update_post_before.php
			if (!empty($update['accesson'])):
				#更新 插入权限
				$newarr = [];
				foreach($grouplist as $_group):
					if($_group['gid']==7):
						continue;
					endif;
					$newarr[$_group['gid']]['fid'] = $_fid;
					$newarr[$_group['gid']]['gid'] = $_group['gid'];
					foreach (['allowread', 'allowthread', 'allowpost', 'allowattach', 'allowdown'] as $x):
						$newarr[$_group['gid']][$x] = empty($allowlist[$_group['gid']][$x])?0:1;
					endforeach;
				endforeach;
				$rows += MyDB::t('forum_access')->insert_map_update($newarr);
			else:
				#删除板块权限
				$update['accesson'] = 0;
				$rows += MyDB::t('forum_access')->delete_by_where(array('fid' => $_fid));
			endif;
			if (!empty($_POST['modnames'])):
				#版主UID
				#$moduids = user_names_to_ids($_POST['modnames']); 不支持数字 改进他
				$modnames = explode(',', $_POST['modnames']);
				$where = [];
				foreach ($modnames as $v):
					if (is_numeric($v)):
						$where['uid'][] = intval($v);
					else:
						$where['username'][] = trim($v);
					endif;
				endforeach;
				if (!empty($where)):
					#目标数据明确 应该减少不必要的字段索引
					$useruids = array_column(MyDB::t('user')->select(...MyDB::WHERE_OR($where, '', MyDB::MODE_ALL_NUM, array('uid'))), 0);
					print_r($useruids);
					if (!empty($useruids)):
						$update['moduids'] = implode(',', $useruids);
					endif;
				endif;
			endif;
			$rows += MyDB::t('forum')->insert_update($update);
			// hook admin_forum_update_post_end.php
			if (!empty($rows)):
				forum_list_cache_delete();
				MyApp::message(0, lang('save_successfully'), array('url' => MyApp::purl('forum/list')));
			endif;
			MyApp::message(0, lang('forum_no_update'));
		endif;
		break;
	case 'delete':
		$_fid = intval(MyApp::value(1));
		$_forum = MyDB::t('forum')->whereFirst(['fid' => $_fid], '', array('name','fid'));
		if (MyApp::head('accept') == 'text/event-stream'):
			#每次删除100条主题
			route_admin::forum_delete($_forum,100);
		else:
			empty($_forum) and MyApp::message(-1, lang('forum_not_exists'));
		endif;
		if ($_SERVER['REQUEST_METHOD'] == 'GET'):
			include _include(ADMIN_PATH . "view/htm/forum/delete.htm");
			exit;
		elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
			MyApp::message(-1, lang('error_request'));
		endif;
		break;
	default:
		// hook admin_forum_list.php
		if ($_SERVER['REQUEST_METHOD'] == 'GET'):
			// hook admin_forum_list_get_start.php
			$header['title']        = lang('forum_admin');
			$header['mobile_title'] = lang('forum_admin');
			$newforumlist = MyDB::t('forum')->whereAll([], MyDB::ORDER(['rank' => 'desc', 'fid' => 'asc']), array('name', 'icon', 'rank', 'fid'));
			$newforumlist = array_column($newforumlist, null, 'fid');
			$importjs[] = route_admin::site('view/js/forum-list.js');
			// hook admin_forum_list_get_end.php
			include _include(ADMIN_PATH . "view/htm/forum/list.htm");
			exit;
		elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
			// hook admin_forum_list_post_start.php
			if (!empty(MyApp::head('ajax-fetch'))):
				if (!empty($_FILES['file']) && empty($_FILES['file']['error'])):
					// hook admin_forum_list_post_upload.php
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
				// hook admin_forum_list_post_loop_end.php
				if (!empty($datalist)):
					$row = MyDB::t('forum')->insert_map_update($datalist);
					#更新板块缓存
					if ($row > 0):
						forum_list_cache_delete();
						// hook admin_forum_list_post_end.php
						MyApp::message(0,lang('save_successfully'), array('url' => MyApp::purl('forum/list')));
					endif;
				endif;
			endif;
			// hook admin_forum_list_post_end.php
			MyApp::message(0, lang('forum_no_update'));
		endif;
		break;
endswitch;
// hook admin_forum_end.php
exit;
