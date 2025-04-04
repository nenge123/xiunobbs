<?php
/**
 * @author N <m@nenge.net>
 * 板块更新
 * 接口
 * 文件接口 所有文件保存至upload/forum/
 * 		content-action:attatch/upload $_FILES['file'] 返回文件
 * 		content-action:attatch/list 获取文件列表
 * 		content-action:attatch/big 大文件上传
 * 单独板块字段更新
 * 全部字段更新
 * 有一个BUG 如果权限字段不全写入用户组会产生奇怪问题,等找到问题,
 * 理论题管理员,版主,禁止用户等不参与板块权限设置
 */
!defined('ADMIN_PATH') and exit('Access Denied.');
$_fid = MyApp::value(1);;
$_forum = MyDB::t('forum')->whereFirst(['fid' => $_fid]);
empty($_forum) and MyApp::message(-1, MyApp::Lang('forum_not_exists'));
if ($_SERVER['REQUEST_METHOD'] == 'GET'):
	MyApp::setValue('title',MyApp::Lang('forum_edit').':'.$_forum['name']);
	// hook admin_forumupdate_get_start.php
	#生成权限信息
	$list = MyDB::t('forum_access')->whereAll(array('fid' => $_fid), MyDB::ORDER(['gid' => 'asc']));
	if (empty($list)):
		foreach ($grouplist as $k => $v):
			if(in_array($v['gid'],[1,2,3,4,5,7])):
				//continue;
			endif;
			$accesslist[$k] = $v; // 字段名相同，直接覆盖。 / same field, directly overwrite
		endforeach;
	else:
		$list = array_column($list, null, 'gid');
		foreach ($grouplist as $k => $v):
			if(in_array($v['gid'],[1,2,3,4,5,7])):
				//continue;
			endif;
			$accesslist[$k] = array_merge($grouplist[$k],$list[$k] ?? array());
		endforeach;
	endif;
	#版主列表
	if (empty($_forum['moduids'])):
		$_forum['modnames'] = '';
	else:
		$_forum['modnames'] = implode(',', array_column(MyDB::t('user')->where(['uid' => explode(',', $_forum['moduids'])], MyDB::ORDER(['uid' => 'asc']), 2, array('username')), 0));
	endif;
	// hook admin_forumupdate_get_end.php
	include(route_admin::tpl_link('forum/update.htm'));
	exit;
elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
	// hook admin_forumupdate_post_start.php
	#获取论坛板块的字段
	$forumkeys = MyDB::t('forum')->columns();
	if (!empty(MyApp::head('ajax-fetch'))):
		new model\adminupload($_fid);
	endif;
	if (count($_POST) == 1):
		#单独更新一个字段
		$key = array_keys($_POST)[0];
		if (in_array($key, $forumkeys)):
			// hook admin_forumupdate_post_a_key.php
			if( MyDB::t('forum')->update_by_where($_POST, array('fid' => $_forum['fid'])) ):
				forum_list_cache_delete();
				MyApp::message(0, MyApp::Lang('forum_' . $key) . MyApp::Lang('admin_forum_save_brief'));
			endif;
			MyApp::message(0, MyApp::Lang('forum_no_update'));
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
	// hook admin_forumupdate_post_before.php
	if (!empty($update['accesson'])):
		#更新 插入权限
		$newarr = [];
		foreach($grouplist as $_group):
			if(in_array($_group['gid'],[1,2,3,4,5,7])):
				//continue;
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
	// hook admin_forumupdate_post_end.php
	if (!empty($rows)):
		forum_list_cache_delete();
		MyApp::message(0, MyApp::Lang('save_successfully'), array('url' => MyApp::purl('forum/list')));
	endif;
	MyApp::message(0, MyApp::Lang('forum_no_update'));
endif;