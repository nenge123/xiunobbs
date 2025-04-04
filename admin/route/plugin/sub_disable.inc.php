<?php

/**
 * @author N <m@nenge.net>
 * 插件关闭
 */
!defined('ADMIN_PATH') and exit('Access Denied.');
 route_admin::plugin_lock();
 // 禁用插件
 $plugin['enable'] = 0;
 route_admin::plugin_save($plugin,$dir);
 route_admin::clear_tmp();
 $msg = MyApp::Lang('plugin_disable_sucessfully', array('name' => $name));
 MyApp::message(0,MyApp::Lang('plugin_disable_sucessfully', array('name' => $name)),['url'=>MyApp::url('plugin/read/'.$dir)]);