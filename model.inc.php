<?php

!defined('DEBUG') AND exit('Forbidden');

// 可以合并成一个文件，加快速度
// merge to one file.

// hook model_inc_start.php

#没有注入点
include(APP_PATH.'model/kv.func.php');
include(APP_PATH.'model/queue.func.php');
include(APP_PATH.'model/old.func.php');
$include_model_files = array (
	#APP_PATH.'model/kv.func.php',
	#APP_PATH.'model/queue.func.php',
	APP_PATH.'model/group.func.php',
	APP_PATH.'model/user.func.php',
	APP_PATH.'model/forum.func.php',
	APP_PATH.'model/forum_access.func.php',
	APP_PATH.'model/thread.func.php',
	APP_PATH.'model/thread_top.func.php',
	APP_PATH.'model/post.func.php',
	APP_PATH.'model/attach.func.php',
	APP_PATH.'model/check.func.php',
	APP_PATH.'model/mythread.func.php',
	APP_PATH.'model/table_day.func.php',
	#APP_PATH.'model/cron.func.php',
	APP_PATH.'model/form.func.php',
	APP_PATH.'model/misc.func.php',
	
	// hook model_inc_file.php
	
);

// hook model_inc_include_before.php

foreach ($include_model_files as $model_files) {
	include _include($model_files);
}

// hook model_inc_end.php









/*
function xn_php_strip_whitespace($file) {
	$s = php_strip_whitespace($file);
	if(substr($s, 0, 5) == '<?php') {
		$s = substr($s, 5);
	}
	if(substr($s, -2) == '?>') {
		$s = substr($s, 0, -2);
	}
	return $s;
}*/

?>