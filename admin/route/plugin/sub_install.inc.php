<?php

/**
 * @author N <m@nenge.net>
 * 插件 安装
 */
!defined('APP_PATH') and exit('Access Denied.');
route_admin::plugin_lock();
// 卸载同类插件，防止安装类似插件。
// 自动卸载掉其他已经安装的主题 / automatically unstall other theme plugin.
if (str_contains($dir, 'theme')):
	$pluginlist = plugin::read_plugin_data();
	foreach ($pluginlist as $_dir => $_plugin):
		if (str_contains($_dir, 'theme')):
			if ($dir == $_dir) continue;
			if(!empty($_plugin['dependencies'])):
				#连同依赖一同关闭
				foreach ($_plugin['dependencies'] as $_subdir => $_version):
					$pluginlist[$_subdir]['installed'] = 0;
					$pluginlist[$_subdir]['enable'] = 0;
					if(route_admin::plugin_save($pluginlist[$_subdir],$_subdir)):
						$_install =  plugin::path($_subdir . '/unstall.php');
						if (is_file($_install)):
							include _include($_install);
						endif;
					endif;
				endforeach;
			endif;
			$_plugin['installed'] = 0;
			$_plugin['enable'] = 0;
			if(route_admin::plugin_save($_plugin,$_dir)):
				$_install =  plugin::path($_dir. '/unstall.php');
				if (is_file($_install)):
					include _include($_install);
				endif;
			endif;
		endif;
	endforeach;
endif;
if(!empty($plugin['dependencies'])):
	#依赖项
	$pluginlist = plugin::read_plugin_data();
	foreach($plugin['dependencies'] as $_dir=>$version):
		$_info =  array('s'=>$_dir.'('.$version.')');
		if(empty($pluginlist[$_dir])):
			#插件不存在
			route_admin::plugin_unlock();
			message(-1, MyApp::Lang('plugin_dependency_following',$_info));
		elseif(version_compare($version,$pluginlist[$_dir]['version'],'<')):
			#版本不对
			route_admin::plugin_unlock();
			message(-1, MyApp::Lang('plugin_dependency_following',$_info));
		endif;
	endforeach;
endif;
// 安装插件 / install plugin
$plugin['installed'] = 1;
$plugin['enable'] = 1;
if(route_admin::plugin_save($plugin,$dir)):
	$installfile =  plugin::path($dir . '/install.php');
	if (is_file($installfile)):
		include _include($installfile);
	endif;
endif;
route_admin::clear_tmp();
$msg = MyApp::Lang('plugin_install_sucessfully', array('name' => $name));
message(0, jump($msg, MyApp::url('plugin/read/'.$dir), 3));