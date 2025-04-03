
<?php

/**
 * @author N <m@nenge.net>
 * 设置
 * 清空缓存
 */
!defined('APP_PATH') and exit('Access Denied.');
if ($_SERVER['REQUEST_METHOD'] == 'GET'):
	// hook admin_settingclear_get.php
	$input = array();
	$input['clear_tmp'] = form_checkbox('clear_tmp', 1);
	$input['clear_cache'] = form_checkbox('clear_cache', 1);
	include _include(ADMIN_PATH . 'view/htm/setting/clear.htm');
elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
	// hook admin_settingclear_post.php
	$clear_tmp = MyApp::post('clear_tmp',0);
	$clear_cache =  MyApp::post('clear_cache',0);
	if (!empty($clear_tmp)):
		MyApp::remove_file(MyApp::tmp_path(), 1);
	endif;
	if (!empty($clear_cache)):
		cache_truncate();
		$runtime = NULL; // 清空
	endif;
	// hook admin_settingclear_post_end.php
	MyApp::message(0, lang('admin_clear_successfully'));
endif;
