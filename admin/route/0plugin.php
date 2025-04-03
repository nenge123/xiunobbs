<?php
!defined('APP_PATH') and exit('Access Denied.');
$action = MyApp::value(0);
$dir = MyApp::value(1);
switch ($action):
	case 'install':
		route_admin::plugin_lock();
		$plugin = plugin::get_plugin_json($dir);
		empty($plugin) and message(-1, MyApp::Lang('plugin_not_exists'));
		$name = $plugin['name'];
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
		break;
	case 'unstall':
		route_admin::plugin_lock();
		$plugin = plugin::get_plugin_json($dir);
		empty($plugin) and message(-1, MyApp::Lang('plugin_not_exists'));
		$name = $plugin['name'];
		if(!empty($plugin['dependencies'])):
			#连同依赖一同关闭
			$pluginlist = plugin::read_plugin_data();
			foreach ($plugin['dependencies'] as $_dir => $_version):
				if(!empty($pluginlist[$_dir]['enable'])):
					$_info =  array('s'=>$_dir.'('.$_version.')');
					route_admin::plugin_unlock();
					message(-1, MyApp::Lang('plugin_being_dependent_cant_delete',$_info));
				endif;
			endforeach;
		endif;
		$plugin['installed'] = 0;
		$plugin['enable'] = 0;
		if(route_admin::plugin_save($plugin,$dir)):
			$unstallfile = plugin::path($dir . '/unstall.php');
			if (is_file($unstallfile)):
				include _include($unstallfile);
			endif;
			// 卸载插件
		endif;
		route_admin::clear_tmp();
		$msg = MyApp::Lang('plugin_unstall_sucessfully', array('name' => $name, 'dir' =>'plugin/'.$dir));
		message(0, jump($msg, MyApp::url('plugin/read/'.$dir), 5));
		break;
	case 'enable':
		route_admin::plugin_lock();
		$plugin = plugin::get_plugin_json($dir);
		empty($plugin) and message(-1, MyApp::Lang('plugin_not_exists'));
		$name = $plugin['name'];
		// 启用插件
		$plugin['enable'] = 1;
		route_admin::plugin_save($plugin,$dir);
		route_admin::clear_tmp();
		$msg = MyApp::Lang('plugin_enable_sucessfully', array('name' => $name));
		message(0, jump($msg, MyApp::url('plugin/read/'.$dir), 1));
		break;
	case 'disable':
		route_admin::plugin_lock();
		$plugin = plugin::get_plugin_json($dir);
		empty($plugin) and message(-1, MyApp::Lang('plugin_not_exists'));
		$name = $plugin['name'];
		// 禁用插件
		$plugin['enable'] = 0;
		route_admin::plugin_save($plugin,$dir);
		route_admin::clear_tmp();
		$msg = MyApp::Lang('plugin_disable_sucessfully', array('name' => $name));
		message(0, jump($msg, MyApp::url('plugin/read/'.$dir), 3));
		break;
	case 'setting':
		$plugin = plugin::get_plugin_json($dir);
		$name = $plugin['name'];;
		if(is_file(plugin::path($dir . '/setting.php'))):
			include _include(plugin::path($dir . '/setting.php'));
		else:
			message(-1,'此插件没有设置!');
		endif;
		break;
	case 'read':
		$plugin = plugin::get_plugin_json($dir);
		empty($plugin) and message(-1, MyApp::Lang('plugin_not_exists'));
		$islocal = true;
		$url = '';
		$download_url = '';
		$errmsg = '';
		$tab = !$islocal ? ($plugin['price'] > 0 ? 'official_fee' : 'official_free') : 'local';
		$header['title']    = MyApp::Lang('plugin_detail') . '-' . $plugin['name'];
		include _include(ADMIN_PATH . 'view/htm/plugin_read.htm');
		break;
	default:
		// 初始化插件变量 / init plugin var
		// 本地插件 local plugin list
		$pluginlist = plugin::read_plugin_data();
		$pagination = '';
		$pugin_cate_html = '';
		$header['title']    = MyApp::Lang('local_plugin');
		include _include(ADMIN_PATH . 'view/htm/plugin_list.htm');
		break;
endswitch;