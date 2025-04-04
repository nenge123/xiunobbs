<?php

/**
 * @author N <m@nenge.net>
 * 插件起始页
 * 统计信息
 */
!defined('APP_PATH') and exit('Access Denied.');
// 初始化插件变量 / init plugin var
// 本地插件 local plugin list
$dir = MyApp::value(0);
$base_action = array('disable','enable','install', 'unstall');
if (!empty($dir)&&!in_array($dir,$base_action)):
	#读取缓存
	define('PLUGIN_DIR', plugin::path($dir . DIRECTORY_SEPARATOR));
endif;
if (defined('PLUGIN_DIR') && is_dir(PLUGIN_DIR)):
	$nav_common = array('install', 'unstall', 'setting', 'disable', 'enable','read');
	#读取插件信息
	$plugin = plugin::get_plugin_json($dir);
	if(!empty($plugin)&&!empty($plugin['name'])):
		#是否有效插件信息
		$action = MyApp::value(1);
		$name = $plugin['name'];
		$url_icon = plugin::site($dir . '/icon.png');
		$url_base = MyApp::purl($dir);
		$url_install = MyApp::purl($dir . '/install');
		$url_unstall = MyApp::purl($dir . '/unstall');
		$url_setting = MyApp::purl($dir . '/setting');
		$url_disable = MyApp::purl($dir . '/disable');
		$url_enable = MyApp::purl($dir . '/enable');
		$url_admin = plugin::path($dir . '/route.inc.php');
		if (in_array($action,$nav_common)):
			include \plugin::parseFile(route_admin::path('route/plugin/sub_' . $action . '.inc.php'));
			exit;
		endif;
		#插件自带路由
		if ($action != 'read' && is_file($url_admin)):
			include($url_admin);
			exit;
		endif;
		include \plugin::parseFile(route_admin::path('route/plugin/sub_read.inc.php'));
		exit;
	endif;
endif;
$pluginlist = plugin::read_plugin_data();
switch($dir):
	case 'install':
		$pluginlist = array_filter($pluginlist,fn($m)=>!empty($m['installed']));
	break;
	case 'unstall':
		$pluginlist = array_filter($pluginlist,fn($m)=>empty($m['installed']));
	break;
	case 'disable':
		$pluginlist = array_filter($pluginlist,fn($m)=>!empty($m['installed'])&&empty($m['enable']));
	break;
	case 'all':
	break;
	default:
		$pluginlist = array_filter($pluginlist,fn($m)=>!empty($m['installed'])&&!empty($m['enable']));
	break;
endswitch;
include(route_admin::tpl_link('plugin/list.htm'));
