<?php
!defined('APP_PATH') and exit('Access Denied.');
$action = MyApp::value(0);

// 不允许删除的版块 / system keeped forum
$system_forum = array(1);
// hook admin_forum_start.php
switch ($action):
	case 'update':
		$_fid = MyApp::value(1);;
		$_forum = forum_read($_fid);
		empty($_forum) and message(-1, lang('forum_not_exists'));
		if ($_SERVER['REQUEST_METHOD'] == 'GET'):
			//user_ids_to_names($_forum['moduids'])
			$header['title']        = lang('forum_edit');
			$header['mobile_title'] = lang('forum_edit');
			// hook admin_forum_update_get_start.php
			$accesslist = forum_access_find_by_fid($_fid);
			if (empty($accesslist)):
				foreach ($grouplist as $group):
					$accesslist[$group['gid']] = $group; // 字段名相同，直接覆盖。 / same field, directly overwrite
				endforeach;
			else:
				foreach ($accesslist as &$access):
					$access['name'] = $grouplist[$access['gid']]['name']; // 字段名相同，直接覆盖。 / same field, directly overwrite
				endforeach;
			endif;
			if (empty($_forum['modlist'])):
				$_forum['modnames'] = '';
			else:
				$_forum['modnames'] = implode(',', array_column($_forum['modlist'], 'username'));
			endif;
			// hook admin_forum_update_get_end.php
			$importjs[] = route_admin::site('view/js/forum-update.js');
			include _include(ADMIN_PATH . "view/htm/forum/update.htm");
			exit;
		elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
			// hook admin_forum_update_post_start.php
			if (!empty(MyApp::head('ajax-fetch'))):
				new model\adminupload($_fid);
			endif;
			if (count($_POST) == 1):
				$key = array_keys($_POST)[0];
				if (isset($_forum[$key])):
					MyDB::t('forum')->update_by_where($_POST, array('fid' => $_forum['fid']));
					$ajax = 1;
					$_SERVER['ajax'] = 1;
					message(0, lang('forum_' . $key) . lang('admin_forum_save_brief'));
				endif;
			endif;
			#获取论坛板块的字段
			$forumkeys = MyDB::t('forum')->columns();
			$update = [];
			$allowlist = [];
			$rows = 0;
			foreach ($_POST as $k => $v):
				if (in_array($k, $forumkeys)):
					$update[$k] = $v;
				elseif (str_starts_with($k, 'allow')):
					foreach ($v as $x => $y):
						$allowlist[$x][$k] = $y;
					endforeach;
				endif;
			endforeach;
			// hook admin_forum_update_post_before.php
			if (!empty($update['accesson'])):
				#更新 插入权限
				foreach ($allowlist as $k => $v):
					$allowlist[$k]['fid'] = $_fid;
					$allowlist[$k]['gid'] = $k;
					foreach (['allowread', 'allowthread', 'allowpost', 'allowattach', 'allowdown'] as $x):
						if (empty($v[$x])):
							$allowlist[$k][$x] = 0;
						endif;
					endforeach;
				endforeach;
				$rows += MyDB::t('forum_access')->insert_map_update($allowlist);
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
		$_fid = MyApp::value(1);;
		$_forum = forum_read($_fid);
		empty($_forum) and MyApp::message(-1, lang('forum_not_exists'));
		if ($_SERVER['REQUEST_METHOD'] == 'GET'):
			include _include(ADMIN_PATH . "view/htm/forum/delete.htm");
			exit;
		elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
			// hook admin_forum_delete_start.php
			if(MyDB::t('thread')->whereCount(array('fid'=>$_fid))):
				#先删除主题?
				MyApp::message(-1, lang('forum_delete_thread_before_delete_forum'));
			endif;
			if (isset($_forum['fup'])):
				#不能删除子版块功能
				foreach ($forumlist as $k => $v):
					if ($v['fup'] == $_fid):
						MyApp::message(-1, lang('forum_please_delete_sub_forum'));
					endif;
				endforeach;
			endif;
			forum_delete($_fid);
			forum_list_cache_delete();
			// hook admin_forum_delete_end.php
			MyApp::message(0, lang('forum_delete_successfully'),array('url'=>MyApp::purl('forum/list')));
		endif;
		break;
	default:
		// hook admin_forum_list.php
		if ($_SERVER['REQUEST_METHOD'] == 'GET'):
			// hook admin_forum_list_get_start.php
			$header['title']        = lang('forum_admin');
			$header['mobile_title'] = lang('forum_admin');
			$importjs[] = route_admin::site('view/js/forum-list.js');
			// hook admin_forum_list_get_end.php
			include _include(ADMIN_PATH . "view/htm/forum/list.htm");
			exit;
		elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
			// hook admin_forum_list_post_start.php
			if (!empty(MyApp::head('ajax-fetch'))):
				if (!empty($_FILES['file']) && empty($_FILES['file']['error'])):
					// hook admin_forum_list_post_upload.php
					$imagepath = MyApp::path('upload/forum/' . MyApp::post('fid') . '.png');
					if (move_upload_file($_FILES['file']['tmp_name'], $imagepath)):
						MyApp::message_json(
							array('url' => MyApp::convert_site($imagepath), 'icon' => $_SERVER['REQUEST_TIME'])
						);
					else:
						MyApp::message(-1, '上传失败');
					endif;
				endif;
			endif;
			if (isset($_POST['fid']) && is_array($_POST['fid'])):
				$datalist = array();
				$keys = array_keys($_POST);
				$forumkeys = MyDB::t('forum')->columns();
				foreach ($_POST['fid'] as $k => $v):
					foreach ($keys as $x):
						if (!in_array($x, $forumkeys)) continue;
						$datalist[$v][$x] = $_POST[$x][$k];
					endforeach;
				endforeach;
				// hook admin_forum_list_post_loop_end.php
				if (!empty($datalist)):
					$row = MyDB::t('forum')->insert_map_update($datalist, 'fid');
					#更新板块缓存
					if ($row):
						forum_list_cache_delete();
						// hook admin_forum_list_post_end.php
						MyApp::message(0, lang('save_successfully'), array('url' => MyApp::purl('forum/list')));
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
