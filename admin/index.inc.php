<?php

!defined('APP_PATH') and exit('Access Denied.');
// 只允许管理员登陆后台
// 管理组检查 / check admin group
if ($gid != 1):
	MyApp::http_location(MyApp::topurl('index'));
endif;
$admin_token = route_admin::admin_token_check();
$route = MyApp::value('module', 'index');
$action = MyApp::value(0, 'index');
if (empty($admin_token)):
	if ($route != 'index' || $action != 'login'):
		#改为跳转 避免误用接口
		MyApp::http_location(MyApp::url('index/login'));
	endif;
endif;
$menus = include _include(ADMIN_PATH . 'menu.conf.php');
MyApp::app()->datas['menus'] = $menus;
// hook admin_index_menu_after.php
switch ($route):
		#兼容旧版
		// hook admin_index_route_case_start.php
		// hook admin_index_route_case_end.php
	default:
		break;
endswitch;
if (headers_sent()):
	exit;
endif;
if (isset($menus[$route])):
	#设置默认标题
	if (!empty($action) && isset($menus[$route]['tab'][$action])):
		$header['title'] = $menus[$route]['tab'][$action]['text'];
		MyApp::setValue('title', $menus[$route]['tab'][$action]['text']);
	elseif (isset($menus[$route]['text'])):
		$header['title'] = $menus[$route]['text'];
		MyApp::setValue('title', $menus[$route]['text']);
	else:
		$header['title'] = $menus['index']['text'];
		MyApp::setValue('title', $menus['index']['text']);
	endif;
endif;
$routefile = route_admin::path('route/' . $route . '/' . $action . '.inc.php');
// hook admin_index_route_case_default.php
if (is_file($routefile)):
	include _include($routefile);
	exit;
endif;
$routefile = route_admin::path('route/' . $route . '.php');
if (is_file($routefile)):
	include _include($routefile);
	exit;
endif;
$routefile = route_admin::path('route/' . $route . '/index.inc.php');
if (is_file($routefile)):
	if (!empty($action) && isset($menus[$route]['tab']['index'])):
		$header['title'] = $menus[$route]['tab']['index']['text'];
		MyApp::setValue('title', $menus[$route]['tab']['index']['text']);
	endif;
	include _include($routefile);
	exit;
endif;
include _include(ADMIN_PATH . 'route/index/index.inc.php');
exit;
switch ($route) {
	// hook admin_index_route_case_start.php
	case 'index':
		include _include(ADMIN_PATH . 'route/index.php');
		break;
	case 'setting':
		include _include(ADMIN_PATH . 'route/setting.php');
		break;
	case 'forum':
		include _include(ADMIN_PATH . 'route/forum.php');
		break;
	case 'friendlink':
		include _include(ADMIN_PATH . 'route/friendlink.php');
		break;
	case 'group':
		include _include(ADMIN_PATH . 'route/group.php');
		break;
	case 'other':
		include _include(ADMIN_PATH . 'route/other.php');
		break;
	case 'user':
		include _include(ADMIN_PATH . 'route/user.php');
		break;
	case 'thread':
		include _include(ADMIN_PATH . 'route/thread.php');
		break;
	case 'plugin':
		include _include(ADMIN_PATH . 'route/plugin.php');
		break;
	// hook admin_index_route_case_end.php
	default:
		// hook admin_index_route_case_default.php
		include _include(ADMIN_PATH . 'route/index.php');
		break;
		/*
		!is_word($route) AND http_404();
		$routefile = _include(ADMIN_PATH."route/$route.php");
		!is_file($routefile) AND  http_404();
		include $routefile;
		*/
}
