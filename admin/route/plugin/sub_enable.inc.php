<?php

/**
 * @author N <m@nenge.net>
 * 插件启用
 */
!defined('ADMIN_PATH') and exit('Access Denied.');
route_admin::plugin_lock();
// 启用插件
$plugin['enable'] = 1;
route_admin::plugin_save($plugin, $dir);
route_admin::clear_tmp();
MyApp::message(0,MyApp::Lang('plugin_enable_sucessfully', array('name' => $name)), ['url'=>MyApp::url('plugin/read/' . $dir)]);
