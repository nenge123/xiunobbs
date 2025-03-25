<?php

!defined('APP_PATH') and exit('Access Denied.');

$action = param(1, 'cache');

// hook admin_other_start.php

if($action == 'cache') {
	
	// hook admin_other_cache_get_post.php
	
	if($_SERVER['REQUEST_METHOD'] == 'GET') {
		
		// hook admin_other_cache_get_end.php
		
		$input = array();
		$input['clear_tmp'] = form_checkbox('clear_tmp', 1);
		$input['clear_cache'] = form_checkbox('clear_cache', 1);
		include _include(ADMIN_PATH.'view/htm/other_cache.htm');
		
	} else {
		
		$clear_tmp = param('clear_tmp');
		$clear_cache = param('clear_cache');
		
		if(!empty($clear_tmp)):
			MyApp::remove_file(MyApp::tmp_path(),1);
		endif;
		$clear_cache AND cache_truncate();
		$clear_cache AND $runtime = NULL; // 清空
	
		// hook admin_other_cache_post_end.php
		
		message(0, lang('admin_clear_successfully'));
	}
}

// hook admin_other_end.php

?>