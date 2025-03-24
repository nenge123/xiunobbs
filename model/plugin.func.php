<?php

// 本地插件
$plugin_paths = array();
$plugins = array(); // 跟官方插件合并

// 官方插件列表
$official_plugins = array();

function _include($srcfile)
{
	return plugin::parseFile($srcfile);
}

function _include_callback_1($m)
{
	return plugin::parseSlot($m);
}


// 插件依赖检测，返回依赖的插件列表，如果返回为空则表示不依赖
/*
	返回依赖的插件数组：
	array(
		'xn_ad'=>'1.0',
		'xn_umeditor'=>'1.0',
	);
*/
function plugin_dependencies($dir)
{
	global $plugin_srcfiles, $plugin_paths, $plugins;
	$plugin = $plugins[$dir];
	$dependencies = $plugin['dependencies'];

	// 检查插件依赖关系
	$arr = array();
	foreach ($dependencies as $_dir => $version) {
		if (!isset($plugins[$_dir]) || !$plugins[$_dir]['enable']) {
			$arr[$_dir] = $version;
		}
	}
	return $arr;
}

/*
	返回被依赖的插件数组：
	array(
		'xn_ad'=>'1.0',
		'xn_umeditor'=>'1.0',
	);
*/
function plugin_by_dependencies($dir)
{
	global $plugins;

	$arr = array();
	foreach ($plugins as $_dir => $plugin) {
		if (isset($plugin['dependencies'][$dir]) && $plugin['enable']) {
			$arr[$_dir] = $plugin['version'];
		}
	}
	return $arr;
}

// 清空插件的临时目录
function plugin_clear_tmp_dir()
{
	global $conf;
	rmdir_recusive($conf['tmp_path'], TRUE);
	xn_unlink($conf['tmp_path'] . 'model.min.php');
}

function plugin_disable($dir)
{
	global $plugins;

	if (!isset($plugins[$dir])) {
		return FALSE;
	}

	$plugins[$dir]['enable'] = 0;

	//plugin_overwrite($dir, 'unstall');
	//plugin_hook($dir, 'unstall');

	file_replace_var(APP_PATH . "plugin/$dir/conf.json", array('enable' => 0), TRUE);

	plugin_clear_tmp_dir();

	return TRUE;
}

// 安装所有的本地插件
function plugin_install_all()
{
	global $plugins;

	// 检查文件更新
	foreach ($plugins as $dir => $plugin) {
		plugin_install($dir);
	}
}

// 卸载所有的本地插件
function plugin_unstall_all()
{
	global $plugins;

	// 检查文件更新
	foreach ($plugins as $dir => $plugin) {
		plugin_unstall($dir);
	}
}
/*
	插件安装：
		把所有的插件点合并，重新写入文件。如果没有备份文件，则备份一份。
		插件名可以为源文件名：view/header.htm
*/
function plugin_install($dir)
{
	global $plugins, $conf;

	if (!isset($plugins[$dir])) {
		return FALSE;
	}

	$plugins[$dir]['installed'] = 1;
	$plugins[$dir]['enable'] = 1;

	// 1. 直接覆盖的方式
	//plugin_overwrite($dir, 'install');

	// 2. 钩子的方式
	//plugin_hook($dir, 'install');

	// 写入配置文件
	file_replace_var(APP_PATH . "plugin/$dir/conf.json", array('installed' => 1, 'enable' => 1), TRUE);

	plugin_clear_tmp_dir();

	return TRUE;
}

// copy from plugin_install 修改
function plugin_unstall($dir)
{
	global $plugins;
	if (!isset($plugins[$dir])) {
		return TRUE;
	}

	$plugins[$dir]['installed'] = 0;
	$plugins[$dir]['enable'] = 0;

	// 1. 直接覆盖的方式
	//plugin_overwrite($dir, 'unstall');

	// 2. 钩子的方式
	//plugin_hook($dir, 'unstall');

	// 写入配置文件
	file_replace_var(APP_PATH . "plugin/$dir/conf.json", array('installed' => 0, 'enable' => 0), TRUE);

	plugin_clear_tmp_dir();

	return TRUE;
}

// 编译源文件，把插件合并到该文件，不需要递归，执行的过程中 include _include() 自动会递归。
function plugin_compile_srcfile($srcfile)
{
	return plugin::parseCompile($srcfile);
}


function plugin_compile_srcfile_callback($m)
{
	return plugin::read_hook_content($m);
}


function plugin_siteid()
{
	global $conf;
	$auth_key = $conf['auth_key'];
	$siteip = $_SERVER['SERVER_ADDR'];
	$siteid = md5($auth_key . $siteip);
	return $siteid;
}
