<?php
!defined('ADMIN_PATH') and exit('Access Denied.');
// 只允许管理员登陆后台
// 管理组检查 / check admin group
if ($gid != 1):
	MyApp::http_location(MyApp::topurl('index'));
endif;
$admin_token = route_admin::admin_token_check();
$route = MyApp::value('module', 'index');
$action = MyApp::value(0, 'index');
if (empty($admin_token)):
	if ($route != 'login'):
		#改为跳转 避免误用接口
		MyApp::http_location(MyApp::url('login'));
	endif;
elseif($route=='login'):
	MyApp::http_location(MyApp::url());
endif;
$menu = include \plugin::parseFile(route_admin::path('menu.conf.php'));
// hook admin_index_menu_after.php
MyApp::app()->datas['menus'] = $menu;
switch ($route):
		#兼容旧版
		// hook admin_index_route_case_start.php
		// hook admin_index_route_case_end.php
	default:
		if (headers_sent()):
			exit;
		endif;
		if (isset($menu[$route])):
			#设置默认标题
			if (!empty($action) && isset($menu[$route]['tab'][$action])):
				MyApp::setValue('title', $menu[$route]['tab'][$action]['text']);
			elseif (isset($menu[$route]['text'])):
				MyApp::setValue('title', $menu[$route]['text']);
			else:
				MyApp::setValue('title', $menu['index']['text']);
			endif;
		endif;
		$routefile = route_admin::path('route/' . $route . '/' . $action . '.inc.php');
		// hook admin_index_route_case_default.php
		if (is_file($routefile)):
			include \plugin::parseFile($routefile);
			exit;
		endif;
		if (isset($menu[$route]['tab']['index'])):
			MyApp::setValue('title', $menu[$route]['tab']['index']['text']);
		endif;
		$routefile = route_admin::path('route/' . $route . '.inc.php');
		if (is_file($routefile)):
			include \plugin::parseFile($routefile);
			exit;
		endif;
		$routefile = route_admin::path('route/' . $route . '/index.inc.php');
		if (is_file($routefile)):
			include \plugin::parseFile($routefile);
			exit;
		endif;
endswitch;
exit;
switch ($route) {
	// hook admin_index_route_case_start.php
	case 'index':
		include \plugin::parseFile(route_admin::path('route/index.php'));
		break;
	case 'setting':
		include \plugin::parseFile(route_admin::path('route/setting.php'));
		break;
	case 'forum':
		include \plugin::parseFile(route_admin::path('route/forum.php'));
		break;
	case 'friendlink':
		include \plugin::parseFile(route_admin::path('route/friendlink.php'));
		break;
	case 'group':
		include \plugin::parseFile(route_admin::path('route/group.php'));
		break;
	case 'other':
		include \plugin::parseFile(route_admin::path('route/other.php'));
		break;
	case 'user':
		include \plugin::parseFile(route_admin::path('route/user.php'));
		break;
	case 'thread':
		include \plugin::parseFile(route_admin::path('route/thread.php'));
		break;
	case 'plugin':
		include \plugin::parseFile(route_admin::path('route/plugin.php'));
		break;
	// hook admin_index_route_case_end.php
	default:
		// hook admin_index_route_case_default.php
		include \plugin::parseFile(route_admin::path('route/index.php'));
		break;
}