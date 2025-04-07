<?php

/**
 * @author N <m@nenge.net>
 * 插件设置
 */
!defined('ADMIN_PATH') and exit('Access Denied.');
MyApp::setValue('title', $plugin['name']);
include(route_admin::tpl_link('plugin/read.htm'));
exit;