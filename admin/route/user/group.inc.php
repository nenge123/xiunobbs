<?php

/**
 * @author N <m@nenge.net>
 * 用户组
 * 编辑/更新
 */
!defined('APP_PATH') and exit('Access Denied.');
// hook admin_group_list_get_post.php
$system_group = array(0, 1, 2, 4, 5, 6, 7, 101);
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	// hook admin_group_list_get_start.php
	MyApp::setValue('title', MyApp::Lang('group_admin'));
	// hook admin_group_list_get_end.php
	include(route_admin::tpl_link('user/grouplist.htm'));
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if(!empty($_POST['system'])):
		$update = [];
		foreach($system_group as $v):
			$_sysgroup = $_POST['system'][$v]??array('name'=>MyApp::Lang('group_'.$v),'creditsto'=>0);
			$update[$v]['gid'] = $v;
			$update[$v]['name'] = $_sysgroup['name'];
			$update[$v]['creditsfrom'] = 0;
			$update[$v]['creditsto'] = 0;
			if($v==101):
				$_sysgroup['creditsto'] = intval($_sysgroup['creditsto'])?:50;
				$update[$v]['creditsto'] = $_sysgroup['creditsto'];
			endif;
		endforeach;
		if(MyDB::t('group')->insert_map_update($update)>0):
			if(isset($grouplist[101])):
				if($update[101]['creditsto']!=$grouplist[101]['creditsto']):
					MyDB::t('group')->update_by_where(['creditsfrom'=>$update[101]['creditsto']],['creditsfrom'=>$grouplist[101]['creditsto'],'>gid'=>101]);
				endif;
			endif;
			group_list_cache_delete();
			MyApp::message(0, MyApp::Lang('update_successfully'),array('url'=>MyApp::purl('group')));
		endif;
		MyApp::message(-1, MyApp::Lang('data_not_changed'));
	endif;
	if(empty($grouplist[101])):
		MyApp::message(-1,'系统用户组异常,请先设置系统用户组!');
	endif;
	$_result = array();
	$allgids = array_column($grouplist,'gid');
	if(!empty($_POST['delete'])):
		#要删除的用户组
		$delete_arr = explode(',',trim($_POST['delete'],',. '));
		$delete_arr = array_filter($delete_arr,fn($m)=>in_array($m,$allgids));
		$delete_arr = array_filter($delete_arr,fn($m)=>!in_array($m,$system_group));
		$delete_arr = array_unique($delete_arr);
		if(!empty($delete_arr)):
			if(MyDB::t('group')->delete_by_where(['gid'=>$delete_arr])>0):
				$delete_group = array_map(fn($m)=>$grouplist[$m]['name'],$delete_arr);
				$_result[] = '删除了用户组:'.implode(',',$delete_group);
				$allgids = array_filter($allgids,fn($m)=>in_array($m,$delete_arr));
			endif;
		endif;
	endif;
	if(!empty($_POST['member'])):
		#自定义用户组更新!
		foreach($_POST['member'] as $_mid=>$v):
			$v['gid'] = intval($v['gid']);
			if(in_array($v['gid'],$system_group)):
				$_result[] = '用户组更新失败:'. $v['name'].',与系统用户组重复!';
				continue;
			endif;
			$v['creditsfrom'] = intval($v['creditsfrom']);
			$v['creditsto'] = intval($v['creditsto']);
			if(MyDB::t('group')->update_by_where($v,['gid'=>$_mid])>0):
				if($_mid!=$v['gid']):
					$allgids = array_filter($allgids,fn($m)=>$m!=$_mid);
					$allgids[] = $v['gid'];
				endif;
				$newgid[] = $v['gid'];
				$_result[] = '用户组更新:'.$v['name'];
			endif;
		endforeach;
	endif;
	if(!empty($_POST['new'])):
		#设置新增用户
		foreach($_POST['new']['gid'] as $k=>$v):
			$insert = array(
				'gid'=>intval($v),
				'name'=>$_POST['new']['name'][$k],
				'creditsfrom'=>intval($_POST['new']['creditsfrom'][$k]),
				'creditsto'=>($_POST['new']['creditsto'][$k]),
			);
			if(in_array($insert['gid'],$allgids)):
				$_result[] = '用户组添加失败:'. $insert['name'].',用户组GID重复!';
				continue;
			endif;
			#绑定默认用户权限
			$insert = array_merge($grouplist[101],$insert);
			if(MyDB::t('group')->insert_json($insert,MyDB::MODE_AFFECTED_ROWS)>0):
				$_result[] = '用户组添加成功:'. $insert['name'].'!';
			endif;
		endforeach;
	endif;
	if(empty($_result)):
		MyApp::message(-1, MyApp::Lang('data_not_changed'));
	else:
		group_list_cache_delete();
		MyApp::message(0,implode('<br>',$_result),array('url'=>MyApp::purl('group')));
	endif;
	$gidarr = param('_gid', array(0));
	$namearr = param('name', array(''));
	$creditsfromarr = param('creditsfrom', array(0));
	$creditstoarr = param('creditsto', array(0));
	$arrlist = array();

	// hook admin_group_list_post_start.php

	foreach ($gidarr as $k => $v) {
		$arr = array(
			'gid' => $k,
			'name' => $namearr[$k],
			'creditsfrom' => $creditsfromarr[$k],
			'creditsto' => $creditstoarr[$k],
		);
		if (!isset($grouplist[$k])) {
			// 添加 / add
			group_create($arr);
		} else {
			// 编辑 / edit
			group_update($k, $arr);
		}
	}

	// 删除 / delete
	$deletearr = array_diff_key($grouplist, $gidarr);
	foreach ($deletearr as $k => $v) {
		if (in_array($k, $system_group)) continue;
		group_delete($k);
	}

	group_list_cache_delete();

	// hook admin_group_list_post_end.php

	message(0, MyApp::Lang('save_successfully'));
}
