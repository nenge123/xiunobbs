<?php

/**
 * @author N <m@nenge.net>
 * 插件关闭
 */
!defined('APP_PATH') and exit('Access Denied.');
 route_admin::plugin_lock();
 // 禁用插件
 $plugin['enable'] = 0;
 route_admin::plugin_save($plugin,$dir);
 route_admin::clear_tmp();
 $msg = MyApp::Lang('plugin_disable_sucessfully', array('name' => $name));
 message(0, jump($msg, MyApp::url('plugin/read/'.$dir), 3));