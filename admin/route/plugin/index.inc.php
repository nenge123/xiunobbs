<?php

/**
 * @author N <m@nenge.net>
 * 插件起始页
 * 统计信息
 */
!defined('APP_PATH') and exit('Access Denied.');
// 初始化插件变量 / init plugin var
// 本地插件 local plugin list
$pluginlist = plugin::read_plugin_data();
foreach($pluginlist as $k=>$v):
	//array('url'=>MyApp::url('user/create'), 'text'=>MyApp::Lang('admin_user_create')),
	MyApp::app()->datas['menus']['plugin']['tab'][$k] = array(
		'url'=>MyApp::purl($k),
		'text'=>$v['name'],
		'img'=>plugin::site($k . '/icon.png'),
	);
endforeach;
$dir = MyApp::value(0);
if(isset($pluginlist[$dir])):
	$plugin = plugin::get_plugin_json($dir);
	$action = MyApp::value(1);
	$name = $plugin['name'];
	$url_icon = plugin::site($dir . '/icon.png');
	$url_base = MyApp::purl($dir);
	$url_install = MyApp::purl($dir.'/install');
	$url_unstall = MyApp::purl($dir.'/unstall');
	$url_setting = MyApp::purl($dir.'/setting');
	$url_disable = MyApp::purl($dir.'/disable');
	$url_enable = MyApp::purl($dir.'/enable');
	if(in_array($action,['install','unstall','setting','disable','enable'])):
		include _include(
			route_admin::path(
				'route/plugin/sub_'.$action.'.inc.php'
			));
		exit;
	endif;
	MyApp::setValue('title',$plugin['name']);
	include _include(ADMIN_PATH . 'view/htm/plugin/read.htm');
	exit;
endif;
$pagination = '';
$pugin_cate_html = '';
//$header['title']    = MyApp::Lang('local_plugin');
include _include(ADMIN_PATH . 'view/htm/plugin/list.htm');