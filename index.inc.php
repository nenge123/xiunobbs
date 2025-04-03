<?php

!defined('APP_PATH') and exit('Access Denied.');

// hook index_inc_start.php

$sid = MyApp::app()->sess_start();

// 语言 / Language
$lang = MyApp::addLang('bbs.php');
//$_SERVER['lang'] = $lang;

// 用户组 / Group
$grouplist = group_list_cache();

// 支持 Token 接口（token 与 session 双重登陆机制，方便 REST 接口设计，也方便 $_SESSION 使用）
// Support Token interface (token and session dual match, to facilitate the design of the REST interface, but also to facilitate the use of $_SESSION)
$uid = intval(_SESSION('uid'));
empty($uid) and $uid = user_token_get() and $_SESSION['uid'] = $uid;
$user = user_read($uid);

$gid = empty($user) ? 0 : intval($user['gid']);
$group = isset($grouplist[$gid]) ? $grouplist[$gid] : $grouplist[0];

// 版块 / Forum
$fid = 0;
$forumlist = forum_list_cache();
$forumlist_show = forum_list_access_filter($forumlist, $gid);	// 有权限查看的板块 / filter no permission forum
$forumarr = arrlist_key_values($forumlist_show, 'fid', 'name');

#print_r($forumlist);exit;

// 头部 header.inc.htm 
$header = array(
	'title' => MyApp::conf('sitename'),
	'mobile_title' => '',
	'mobile_link' => './',
	'keywords' => '', // 搜索引擎自行分析 keywords, 自己指定没用 / Search engine automatic analysis of key words, so keep it empty.
	'description' => strip_tags(MyApp::conf('sitebrief')),
	'navs' => array(),
);

// 运行时数据，存放于 cache_set() / runtime data
$runtime = model\runtime::init();

// 检测站点运行级别 / restricted access
check_runlevel();

// 全站的设置数据，站点名称，描述，关键词
// $setting = kv_get('setting');

$route = param(0, 'index');
$route = MyApp::value('module');
//print_r(MyApp::data('querydata'));exit;
//print_r($_REQUEST);exit;
// hook index_inc_route_before.php
if (!defined('SKIP_ROUTE')) {

	// 按照使用的频次排序，增加命中率，提高效率
	// According to the frequency of the use of sorting, increase the hit rate, improve efficiency
	switch ($route) {
		// hook index_route_case_start.php
		case 'index':
			include _include(APP_PATH . 'route/index.php');
			break;
		case 'thread':
			include _include(APP_PATH . 'route/thread.php');
			break;
		case 'forum':
			include _include(APP_PATH . 'route/forum.php');
			break;
		case 'user':
			include _include(APP_PATH . 'route/user.php');
			break;
		case 'my':
			include _include(APP_PATH . 'route/my.php');
			break;
		case 'attach':
			include _include(APP_PATH . 'route/attach.php');
			break;
		case 'post':
			include _include(APP_PATH . 'route/post.php');
			break;
		case 'mod':
			include _include(APP_PATH . 'route/mod.php');
			break;
		case 'browser':
			include _include(APP_PATH . 'route/browser.php');
			break;
		// hook index_route_case_end.php
		case 'admin':
			MyApp::http_location('admin/');
		break;
		case 'install':
			MyApp::http_location('install/');
		break;
		default:
			// hook index_route_case_default.php
			include _include(APP_PATH . 'route/index.php');
			break;
			//http_404();
	}
}

// hook index_inc_end.php
