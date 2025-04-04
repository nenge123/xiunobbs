<?php

/**
 * @author N <m@nenge.net>
 * 插件卸载
 */
!defined('ADMIN_PATH') and exit('Access Denied.');
route_admin::plugin_lock();
if(!empty($plugin['dependencies'])):
	#连同依赖一同关闭
	$pluginlist = plugin::read_plugin_data();
	foreach ($plugin['dependencies'] as $_dir => $_version):
		if(!empty($pluginlist[$_dir]['enable'])):
			$_info =  array('s'=>$_dir.'('.$_version.')');
			route_admin::plugin_unlock();
			MyApp::message(-1, MyApp::Lang('plugin_being_dependent_cant_delete',$_info));
		endif;
	endforeach;
endif;
$plugin['installed'] = 0;
$plugin['enable'] = 0;
if(route_admin::plugin_save($plugin,$dir)):
	$unstallfile = plugin::path($dir . '/unstall.php');
	if (is_file($unstallfile)):
		include \plugin::parseFile($unstallfile);
	endif;
	// 卸载插件
endif;
route_admin::clear_tmp();
$msg = MyApp::Lang('plugin_unstall_sucessfully', array('name' => $name, 'dir' =>'plugin/'.$dir));
MyApp::message(0,$msg, ['url'=>MyApp::url('plugin/read/'.$dir)]);