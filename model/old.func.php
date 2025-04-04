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
/**
 * @deprecated 4.1 废弃函数  
 */
function runtime_init() {
	return model\runtime::init();
}

/**
 * @deprecated 4.1 废弃函数  
 */
function runtime_get($k) {
	return model\runtime::getItem($k);
}

/**
 * @deprecated 4.1 废弃函数  
 */
function runtime_set($k, $v) {
	return \model\runtime::setItem($k,$v);
}

/**
 * @deprecated 4.1 废弃函数  
 */
function runtime_delete($k) {
	return \model\runtime::removeItem($k);
}

/**
 * @deprecated 4.1 废弃函数  
 */
function runtime_save() {
	return \model\runtime::save();
}
/**
 * @deprecated 4.1 废弃函数  
 */
function runtime_truncate() {
	return \model\runtime::clear();
}
/**
 * @deprecated 4.1 废弃函数  
 * 计划任务
 */
function cron_run($force = 0) {
	return \model\runtime::cron($force);
}

/**
 * @deprecated 4.1 废弃函数  
 *  此处的 $db 是局部变量，要注意，它返回后在定义为全局变量，可以有多个实例。
 */
function db_new($dbconf)
{
	return MyDB::create($dbconf);
	$type = $dbconf['type'] ?? false;
	if ($type) {
		//print_r($dbconf);
		// 代码不仅仅是给人看的，更重要的是给编译器分析的，不要玩 $db = new $dbclass()，那样不利于优化和 opcache 。
		switch ($type) {
			case 'mysql':
				$db = new MyDB($dbconf['mysql']);
				break;
			case 'pdo_mysql':
				$db = new MyDB($dbconf['pdo_mysql'], 'mysql');
				break;
			case 'pdo_sqlite':
				$db = new MyDB($dbconf['pdo_sqlite'], 'sqlite');
				break;
			case 'pdo_mongodb':
				$db = new MyDB($dbconf['pdo_mongodb'], 'mongodb');
				break;
			default:
				return xn_error(-1, 'Not suppported db type:' . $dbconf['type']);
		}
		if (empty($db)) {
			return NULL;
		}
		return $db;
	}
	return NULL;
}


/**
 * @deprecated 4.1 废弃函数  
 *
 */
function db_connect($d = NULL) {}

/**
 * @deprecated 4.1 废弃函数  
 *
 */
function db_close($d = NULL) {}
// 保存 $db 错误到全局
/**
 * @deprecated 4.1 废弃函数  
 */
function db_errno_errstr($r, $d = NULL, $sql = '')
{
	#global $errno, $errstr;
	if ($r === FALSE) { //  && $d->errno != 0
		$errno = MyDB::rdb()->errorCode();
		$errstr = db_errstr_safe($errno,MyDB::rdb()->errorMessage());
		$s = 'SQL:' . $sql . "\r\nerrno: " . $errno . ", errstr: " . $errstr;
		xn_log($s, 'db_error');
	}
}

// 安全的错误信息
/**
 * @deprecated 4.1 废弃函数  
 */
function db_errstr_safe($errno, $errstr='')
{
	if (DEBUG) return $errstr;
	if ($errno == 1049) {
		return '数据库名不存在，请手工创建';
	} elseif ($errno == 2003) {
		return '连接数据库服务器失败，请检查IP是否正确，或者防火墙设置';
	} elseif ($errno == 1024) {
		return '连接数据库失败';
	} elseif ($errno == 1045) {
		return '数据库账户密码错误';
	}
	return $errstr;
}

/**
 * @deprecated 4.1 废弃函数  
 * 应采用更安全的预处理语句:MyDB::t('table')->select(...MyDB::db_cond_to_sqladd($arr));  
 * $where = MyDB::db_cond_to_sqladd($arr);  
 * 应采用更安全的预处理语句:MyDB::t('table')->select($where[0].' LIMIT 1',$where[1],1);  
 */
function db_cond_to_sqladd($cond)
{
	$s = '';
	if (!empty($cond)) {
		$s = ' WHERE ';
		foreach ($cond as $k => $v) {
			if (!is_array($v)) {
				$v = (is_int($v) || is_float($v)) ? $v : "'" . addslashes($v) . "'";
				$s .= "`$k`=$v AND ";
			} elseif (isset($v[0])) {
				// OR 效率比 IN 高
				$s .= '(';
				//$v = array_reverse($v);
				foreach ($v as $v1) {
					$v1 = (is_int($v1) || is_float($v1)) ? $v1 : "'" . addslashes($v1) . "'";
					$s .= "`$k`=$v1 OR ";
				}
				$s = substr($s, 0, -4);
				$s .= ') AND ';

				/*
				$ids = implode(',', $v);
				$s .= "$k IN ($ids) AND ";
				*/
			} else {
				foreach ($v as $k1 => $v1) {
					if ($k1 == 'LIKE') {
						$k1 = ' LIKE ';
						$v1 = "%$v1%";
					}
					$v1 = (is_int($v1) || is_float($v1)) ? $v1 : "'" . addslashes($v1) . "'";
					$s .= "`$k`$k1$v1 AND ";
				}
			}
		}
		$s = substr($s, 0, -4);
	}
	return $s;
}
/**
 * @deprecated  4.1 废弃函数  
 * MyDB::ORDER(['tid'=>'desc'])
 */
function db_orderby_to_sqladd($orderby)
{
	$s = '';
	if (!empty($orderby)) {
		$s .= ' ORDER BY ';
		$comma = '';
		foreach ($orderby as $k => $v) {
			$s .= $comma . "`$k` " . ($v == 1 ? ' ASC ' : ' DESC ');
			$comma = ',';
		}
	}
	return $s;
}

/**
 * @deprecated 4.1 废弃函数  
 * 应采用更安全的预处理语句:MyDB::t('table')->update(...MyDB::xn_sql_where($arr));  
 * $where = MyDB::xn_sql_where($arr);  
 * 应采用更安全的预处理语句:MyDB::t('table')->update($where[0],$where[1]);  
 * 
 */
function db_array_to_update_sqladd($arr)
{
	$s = '';
	foreach ($arr as $k => $v) {
		$v = addslashes($v);
		$op = substr($k, -1);
		if ($op == '+' || $op == '-') {
			$k = substr($k, 0, -1);
			$v = (is_int($v) || is_float($v)) ? $v : "'$v'";
			$s .= "`$k`=$k$op$v,";
		} else {
			$v = (is_int($v) || is_float($v)) ? $v : "'$v'";
			$s .= "`$k`=$v,";
		}
	}
	return substr($s, 0, -1);
}

/**
 * @deprecated version 4.1 废弃函数  
 * 应采用更安全的预处理语句:MyDB::t('table')->insert(...MyDB::xn_sql_insert($arr));
 */
function db_array_to_insert_sqladd($arr)
{
	$s = '';
	$keys = array();
	$values = array();
	foreach ($arr as $k => $v) {
		$k = addslashes($k);
		$v = addslashes($v);
		$keys[] = '`' . $k . '`';
		$v = (is_int($v) || is_float($v)) ? $v : "'$v'";
		$values[] = $v;
	}
	$keystr = implode(',', $keys);
	$valstr = implode(',', $values);
	$sqladd = "($keystr) VALUES ($valstr)";
	return $sqladd;
}