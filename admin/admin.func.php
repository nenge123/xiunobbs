<?php

// hook admin_func_start.php

// 有部分用户
define('XN_ADMIN_BIND_IP', array_value($conf, 'admin_bind_ip'));

/**
 * 后台检查登录函数
 */
function admin_token_check() {
	global $longip, $conf;
	$useragent_md5 = md5($_SERVER['HTTP_USER_AGENT']);
	
	//$key = md5($longip.$useragent_md5.MyApp::conf('auth_key')); // 有些环境是动态 IP
	$key = md5((XN_ADMIN_BIND_IP ? $longip : '').$useragent_md5.xn_key());
	
	// hook admin_token_check_start.php
	
	$admin_token = param('bbs_admin_token');
	if(empty($admin_token)) {
		$_REQUEST[0] = 'index';
		$_REQUEST[1] = 'login';
	} else {
		$s = xn_decrypt($admin_token, $key);
		if(empty($s)) {
			MyApp::cookies('admin_token', '');
			//message(-1, lang('admin_token_error'));
			message(-1, lang('admin_token_expiry'));
		}
		list($_ip, $_time) = explode("\t", $s);
		
		// 后台超过 3600 自动退出。
		// Background / more than 3600 automatic withdrawal.
		//if($_ip != $longip || $_SERVER['REQUEST_TIME'] - $_time > 3600) {
		if((XN_ADMIN_BIND_IP && $_ip != $longip || !XN_ADMIN_BIND_IP) && $_SERVER['REQUEST_TIME'] - $_time > 3600) {
			MyApp::cookies('admin_token', '');
			message(-1, lang('admin_token_expiry'));
		}
		
		// 超过半小时，重新发新令牌，防止过期
		// More than half an hour, reset a new token, prevent expired
		if($_SERVER['REQUEST_TIME'] - $_time > 1800) {
			admin_token_set();
		}
	}
	// hook admin_token_check_end.php
}

function admin_token_set() {
	global $longip, $conf;
	$useragent_md5 = md5($_SERVER['HTTP_USER_AGENT']);
	//$key = md5($longip.$useragent_md5.MyApp::conf('auth_key'));
	$key = md5((XN_ADMIN_BIND_IP ? $longip : '').$useragent_md5.xn_key());
	
	// hook admin_token_set_start.php
	
	$admin_token = param('bbs_admin_token');
	$s = $longip.'	'.$_SERVER['REQUEST_TIME'];
	
	$admin_token = xn_encrypt($s, $key);
	MyApp::cookies('admin_token', $admin_token, $_SERVER['REQUEST_TIME'] + 3600);
	
	// hook admin_token_set_end.php
}

function admin_token_clean() {
	MyApp::cookies('admin_token', '');
	
	// hook admin_token_clean_start.php
}

// bootstrap style
function admin_tab_active($arr, $active) {
	// hook admin_tab_active_start.php
	$s = '';
	foreach ($arr as $k=>$v) {
		$s .= '<a role="button" class="btn btn-secondary'.($active == $k ? ' active' : '').'" href="'.$v['url'].'">'.$v['text'].'</a>';
	}
	// hook admin_tab_active_end.php
	return $s;
}

// hook admin_func_end.php

?>