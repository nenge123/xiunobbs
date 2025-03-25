<?php
/**
 * 重启session
 *@deprecated  4.1
 */
function sess_restart() {
	return MyApp::app()->sess_restart();
}
/**
 * 启动session
 *@deprecated  4.1
 */
function sess_start() {
	return MyApp::app()->sess_start();
}

function online_count() {
	return db_count('session');
}

function online_find_cache() {
	return db_find('session');
}

function online_list_cache() {
	$onlinelist = cache_get('online_list');
	if($onlinelist === NULL) {
		$onlinelist = db_find('session', array('uid'=>array('>'=>0)), array('last_date'=>-1), 1, 500);
		foreach($onlinelist as &$online) {
			$user = user_read_cache($online['uid']);
			$online['username'] = $user['username'];
			$online['gid'] = $user['gid'];
			$online['ip_fmt'] = long2ip($online['ip']);
			$online['last_date_fmt'] = date('Y-n-j H:i', $online['last_date']);
		}
		cache_set('online_list', $onlinelist, 300);
	}
	return $onlinelist;
}
function runtime_init() {
	return model\runtime::init();
}

function runtime_get($k) {
	return model\runtime::getItem($k);
}

function runtime_set($k, $v) {
	return \model\runtime::setItem($k,$v);
}

function runtime_delete($k) {
	return \model\runtime::removeItem($k);
}

function runtime_save() {
	return \model\runtime::save();
}

function runtime_truncate() {
	return \model\runtime::clear();
}
/**
 * 计划任务
 */
function cron_run($force = 0) {
	return \model\runtime::cron($force);
}