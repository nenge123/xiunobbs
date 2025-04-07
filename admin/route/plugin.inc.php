<?php

/**
 * @author N <m@nenge.net>
 * 插件起始页
 * 统计信息
 */
!defined('ADMIN_PATH') and exit('Access Denied.');
// 初始化插件变量 / init plugin var
// 本地插件 local plugin list
$plugin_dir = MyApp::value(0);
$plugin_action = MyApp::value(1,'index');
$cross_dir = array('disable','enable','install', 'unstall');
if (!empty($plugin_dir)&&!in_array($plugin_dir,$cross_dir)):
	#读取缓存
	define('PLUGIN_DIR', plugin::path($plugin_dir . DIRECTORY_SEPARATOR));
endif;
if (defined('PLUGIN_DIR') && is_dir(PLUGIN_DIR)):
	$cross_action = array('install', 'unstall', 'setting', 'disable', 'enable','read');
	#读取插件信息
	$plugin = plugin::get_plugin_json($plugin_dir);
	if(!empty($plugin)&&!empty($plugin['name'])):
		#是否有效插件信息
		$name = $plugin['name'];
		MyApp::setValue('title',$name);
		$url_icon = plugin::site($plugin_dir . '/icon.png');
		$url_base = MyApp::purl($plugin_dir);
		$url_install = MyApp::purl($plugin_dir . '/install');
		$url_unstall = MyApp::purl($plugin_dir . '/unstall');
		$url_setting = MyApp::purl($plugin_dir . '/setting');
		$url_disable = MyApp::purl($plugin_dir . '/disable');
		$url_enable = MyApp::purl($plugin_dir . '/enable');
		$url_admin = plugin::path($plugin_dir . '/route.inc.php');
		if (in_array($plugin_action,$cross_action)):
			include \plugin::parseFile(route_admin::path('route/plugin/' . $plugin_action . '.sub.php'));
			exit;
		endif;
		#插件自带路由
		if ($plugin_action != 'read' && is_file($url_admin)):
			include($url_admin);
			exit;
		endif;
		include \plugin::parseFile(route_admin::path('route/plugin/sub_read.inc.php'));
		exit;
	endif;
endif;
$pluginlist = plugin::read_plugin_data();
switch($plugin_dir):
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
