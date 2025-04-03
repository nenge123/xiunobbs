<?php

/**
 * @author N <m@nenge.net>
 * 用户
 * 搜索用户
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST'):
	#搜索用户
	$limit = 20;
	$total = 0;
	$page     = intval(MyApp::post('page', 1));
	$colums = array('uid', 'username', 'gid', 'email', 'create_date', 'create_ip');
	$where = array();
	$_uid = MyApp::post('uid');
	$_gid = MyApp::post('gid');
	$_email = MyApp::post('email');
	$_username = MyApp::post('username');
	$_ip = MyApp::post('create_ip');
	if (!empty($_uid)):
		$total = 1;
		$list = MyDB::t('user')->whereAll(['uid' => $_uid], '', $colums);
	else:
		if (!empty($_gid)):
			$where['gid'] = intval($_gid);
		endif;
		if (!empty($_email)):
			$where['%email'] = $_email;
		endif;
		if (!empty($_username)):
			$where['%username'] = $_username;
		endif;
		if (!empty($_ip)):
			if (str_contains($_ip, '::')):
				//ipv6
				MyApp::message(-1, '程序目前不支持IPV6');
			elseif (!preg_match('/\d+\.\d+\.\d+\.\d+/', $_ip)):
				$where['create_ip'] = ip2long($_ip)?:0;
			else:
				MyApp::message(-1, '程序目前不支持IPV4 范围搜索');
			endif;
		endif;
		$total = MyDB::t('user')->whereCount($where);
		$list = MyDB::t('user')->whereAll(
			$where,
			MyDB::ORDER(['uid' => 'asc']) . MyDB::LIMIT($page, $limit),
			$colums
		);
	endif;
	if ($total > 0):
		$maxpage = ceil($total / $limit);
		$pagination = MyApp::pagination($maxpage, $page);
		foreach ($list as $k => $v):
			$list[$k]['groupname'] = $grouplist[$v['gid']]['name'] ?? MyApp::Lang('admin_user_group');
			$list[$k]['create_date_fmt'] = date('Y/m/d', $v['create_date']);
			if (is_numeric($v['create_ip'])):
				$list[$k]['ip'] = long2ip($v['create_ip']);
			else:
				$list[$k]['ip'] = $v['create_ip'];
			endif;
			$list[$k]['url'] = MyApp::purl('update/' . $v['uid']);
		endforeach;
		$userlist = array(
			'list' => $list,
			'page' => $page,
			'maxpage' => $maxpage,
			'total' => $total,
			'pagelist' => $pagination,
			'limit' => $limit
		);
		MyApp::message_json($userlist);
	endif;
	MyApp::message(-1, '无数据');
endif;
include _include(ADMIN_PATH . "view/htm/user/home.htm");
