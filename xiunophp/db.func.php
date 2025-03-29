<?php


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


