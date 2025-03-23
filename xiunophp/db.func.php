<?php
// 此处的 $db 是局部变量，要注意，它返回后在定义为全局变量，可以有多个实例。
function db_new($dbconf)
{
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

function db_sql_find_one($sql, $d = NULL)
{
	return MyDB::query($sql,4);
}

function db_sql_find($sql, $key = NULL, $d = NULL)
{
	return MyDB::query($sql);
}

// 如果为 INSERT 或者 REPLACE，则返回 mysql_insert_id();
// 如果为 UPDATE 或者 DELETE，则返回 mysql_affected_rows();
// 对于非自增的表，INSERT 后，返回的一直是 0
// 判断是否执行成功: mysql_exec() === FALSE
function db_exec($sql, $d = NULL)
{
	return MyDB::exec($sql);
}

function db_count($table, $cond = array(), $d = NULL)
{
	return MyDB::count($table, $cond);
}

function db_maxid($table, $field, $cond = array(), $d = NULL)
{
	$where = MyDB::xn_sql_where($cond);
	return MyDB::t($table)->selectMax($where[0], $where[1], $field);
}

// NO SQL 封装，可以支持 MySQL Marial PG MongoDB
function db_create($table, $arr, $d = NULL)
{
	return MyDB::t($table)->insert(...MyDB::xn_sql_insert($arr));
}

function db_insert($table, $arr, $d = NULL)
{
	return MyDB::t($table)->insert(...MyDB::xn_sql_insert($arr));
}

function db_replace($table, $arr, $d = NULL)
{
	return MyDB::t($table)->replace(...MyDB::xn_sql_insert($arr));
}

function db_update($table, $cond, $update, $d = NULL)
{
	return MyDB::t($table)->update(...MyDB::xn_sql_update($update, $cond));
}

function db_delete($table, $cond, $d = NULL)
{
	return MyDB::t($table)->delete(...MyDB::xn_sql_where($cond));
}

function db_truncate($table, $d = NULL)
{
	return MyDB::t($table)->truncate();
}

function db_read($table, $cond, $d = NULL)
{
	$where = MyDB::xn_sql_where($cond);
	return MyDB::t($table)->select($where[0], $where[1], 4);
}

function db_find($table, $cond = array(), $orderby = array(), $page = 1, $pagesize = 10, $key = '', $col = array(), $d = NULL)
{
	$where = MyDB::xn_sql_where($cond);
	if (!empty($orderby)):
		$where[0] .= MyDB::xn_sql_order($orderby);
	endif;
	$where[0] .= MyDB::LIMIT($page, $pagesize);
	$result =  MyDB::t($table)->select($where[0], $where[1], 1, $col);
	if (!empty($key) && isset($result[0]) && isset($result[0][$key])):
		return array_column($result, null, $key);
	endif;
	return $result;
}

function db_find_one($table, $cond = array(), $orderby = array(), $col = array(), $d = NULL)
{
	$where = MyDB::xn_sql_where($cond);
	if (!empty($orderby)):
		$where[0] .= MyDB::xn_sql_order($orderby);
	endif;
	$where[0] .= MyDB::LIMIT(1);
	return MyDB::t($table)->select($where[0], $where[1], 4, $col);
}

// 保存 $db 错误到全局
function db_errno_errstr($r, $d = NULL, $sql = '')
{
	global $errno, $errstr;
	if ($r === FALSE) { //  && $d->errno != 0
		$errno = $d->errno;
		$errstr = db_errstr_safe($errno, $d->errstr);
		$s = 'SQL:' . $sql . "\r\nerrno: " . $errno . ", errstr: " . $errstr;
		xn_log($s, 'db_error');
	}
}

// 安全的错误信息
function db_errstr_safe($errno, $errstr)
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
