<?php

/**
 * @author N <m@nenge.net>
 * 用户组
 * 编辑/更新 权限
 */
!defined('APP_PATH') and exit('Access Denied.');
$_gid = MyApp::value(1);
if (empty($grouplist[$_gid])):
	MyApp::message(-1, MyApp::Lang('group_not_exists'));
endif;
$system_group = array(0, 1, 2, 4, 5, 6, 7, 101);
$_group = $grouplist[$_gid];
if ($_SERVER['REQUEST_METHOD'] == 'POST'):
	$columns = MyDB::t('group')->columns();
	$allgids = array_column($grouplist, 'gid');
	$update = array();
	foreach ($columns as $key):
		$vaule = MyApp::post($key, 0);
		if (str_starts_with($key, 'allow')):
			$vaule = boolval($vaule);
		elseif (is_numeric($vaule)):
			$vaule = intval($vaule);
		endif;
		$update[$key] = MyApp::post($key, 0);
	endforeach;
	if (!empty($update)):
		if (in_array($_gid, $system_group)):
			#系统用户组不允许修改gid
			unset($update['gid']);
		elseif ($update['gid'] != $_gid && in_array($update['gid'], $allgids)):
			#检查普通用户组不能重复
			MyApp::message(-1, '更新失败,用户组不能重复!');
		endif;
		#直接积分不可更改 不用复杂判断
		unset($update['creditsfrom'],$update['creditsto']);
		$_upwhere = array('gid' => $_gid);
		if (MyDB::t('group')->update_by_where($update, $_upwhere) > 0):
			/*
			if (empty($_POST['credits-auto-update'])):
				if ($_group['creditsfrom'] > 0 && !in_array($update['gid'], $system_group)):
					#非系统用户组且积分不为0 判断是否最低值,更新101
					$newlist = array_filter($grouplist, fn($m) => $m['creditsfrom'] > 0);
					foreach ($newlist as $k => $v):
						$_where = array('gid' => $v['gid']);
						#判断旧数据 结束积分是否匹配其他用户组起始积分
						if ($v['creditsfrom'] === $_group['creditsto']):
							MyDB::t('group')->update_by_where(['creditsfrom' => $update['creditsto']], $_where);
							$xupdate = array('creditsfrom' => $update['creditsto']);
							if ($update['creditsto'] > $v['creditsto']):
								$xupdate['creditsfrom'] = $v['creditsto'] - 1;
								MyDB::t('group')->update_by_where(array('creditsto' => $xupdate['creditsfrom']), $_upwhere);
							endif;
							MyDB::t('group')->update_by_where($xupdate, $_where);
						elseif ($v['creditsto'] === $_group['creditsfrom']):
							#判断旧数据 起始积分是否匹配其他用户组结束积分
							$xupdate = array('creditsto' => $update['creditsfrom']);
							if ($update['creditsfrom'] < $v['creditsfrom']):
								$xupdate['creditsfrom'] = $v['creditsfrom'] + 1;
								MyDB::t('group')->update_by_where(array('creditsfrom' => $xupdate['creditsfrom']), $_upwhere);
							endif;
							MyDB::t('group')->update_by_where(['creditsto' => $update['creditsfrom']], $_where);
						endif;
					endforeach;
					if ($grouplist[101]['creditsto'] === $_group['creditsfrom']):
						MyDB::t('group')->update_by_where(['creditsto' => $update['creditsfrom']], ['gid' => 101]);
					endif;
				elseif ($_gid == 101):
					#101改变
					$newlist = array_filter($grouplist, fn($m) => $m['creditsfrom'] > 0);
					foreach ($newlist as $k => $v):
						if ($v['creditsfrom'] === $_group['creditsto']):
							$_where = array('gid' => $v['gid']);
							$xupdate = array('creditsfrom' => $update['creditsto']);
							if ($update['creditsto'] > $v['creditsto']):
								$xupdate['creditsfrom'] = $v['creditsto'] - 1;
								MyDB::t('group')->update_by_where(array('creditsto' => $xupdate['creditsfrom']), $_upwhere);
							endif;
							MyDB::t('group')->update_by_where($xupdate, $_where);
						endif;
					endforeach;
				endif;
			endif;
			*/
			group_list_cache_delete();
			MyApp::message(0, MyApp::Lang('save_successfully'), ['url' => MyApp::purl('group')]);
		endif;
	endif;
	MyApp::message(-1, MyApp::Lang('data_not_changed'));
endif;
MyApp::setValue('title', MyApp::Lang('group_edit'));
include _include(ADMIN_PATH . "view/htm/user/access.htm");
