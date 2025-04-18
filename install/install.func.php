<?php

function get_env(&$env, &$write) {
	$env['os']['name'] = MyApp::Lang('os');
	$env['os']['must'] = TRUE;
	$env['os']['current'] = PHP_OS;
	$env['os']['need'] = MyApp::Lang('unix_like');
	$env['os']['status'] = 1;
	// glob gzip
	//$env['os']['disable'] = 1;
	
	$env['php_version']['name'] = MyApp::Lang('php_version');
	$env['php_version']['must'] = TRUE;
	$env['php_version']['current'] = PHP_VERSION;
	$env['php_version']['need'] = '8.0';
	$env['php_version']['status'] = version_compare(PHP_VERSION , '8') > 0;

	// 目录可写
	$writedir = array(
		'conf/',
		'log/',
		'tmp/',
		'upload/',
		'plugin/'
	);

	$write = array();
	foreach($writedir as &$dir) {
		$write[$dir] = xn_is_writable(APP_PATH.$dir);
	}
}

function install_sql_file($sqlfile) {
	global $errno, $errstr;
	$s = file_get_contents($sqlfile);
	$s = str_replace(";\r\n", ";\n", $s);
	//$s = preg_replace('/#(.*?)\r\n/i', "", $s);
	$arr = explode(";\n", $s);
	foreach ($arr as $sql) {
		$sql = trim($sql);
		if(empty($sql)) continue;
		$arr = explode(";\n", $s);
		db_exec($sql) === FALSE AND message(-1, "sql: $sql, errno: $errno, errstr: $errstr");
	}
}



?>