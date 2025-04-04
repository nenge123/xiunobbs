<?php

/**
 * @author N <m@nenge.net>
 * 插件设置
 */
!defined('ADMIN_PATH') and exit('Access Denied.');
if(is_file(plugin::path($dir . '/setting.php'))):
	include \plugin::parseFile(plugin::path($dir . '/setting.php'));
else:
	MyApp::message(-1,'此插件没有设置!');
endif;