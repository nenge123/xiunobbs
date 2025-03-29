<?php
!defined('APP_PATH') and exit('Access Denied.');
$action = MyApp::value(0, 'cache');
// hook admin_other_start.php
if ($action == 'cache'):
	// hook admin_other_cache_get_post.php
	if ($_SERVER['REQUEST_METHOD'] == 'GET'):
		// hook admin_other_cache_get_end.php
		$input = array();
		$input['clear_tmp'] = form_checkbox('clear_tmp', 1);
		$input['clear_cache'] = form_checkbox('clear_cache', 1);
		include _include(ADMIN_PATH . 'view/htm/other/cache.htm');
	elseif ($_SERVER['REQUEST_METHOD'] == 'POST'):
		$clear_tmp = MyApp::post('clear_tmp');
		$clear_cache =  MyApp::post('clear_cache');
		if (!empty($clear_tmp)):
			MyApp::remove_file(MyApp::tmp_path(), 1);
		endif;
		if (!empty($clear_cache)):
			cache_truncate();
			$runtime = NULL; // 清空
		endif;
		// hook admin_other_cache_post_end.php
		MyApp::message(0, lang('admin_clear_successfully'));
	endif;
endif;
// hook admin_other_end.php