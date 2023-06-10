<?php

namespace Nenge;

use ArrayObject;
use \PDO;
use \mysqli;

/**
 * MySql驱动
 * @author Nenge
 */
class db_mysqli
{
	public mixed $link;
	public array $query = array();
	/**
	 * 最后语句
	 */
	public string $sql;
	public mixed $conf;
	public float $time;
	private array $settings = array(
		'PDO' => false,
		'MySqli' => false,
		'conf' => array(
			'host' => 'localhost',
			'port' => 3306,
			'charset' => 'utf8mb4',
			'dbname' => ''
		),
	);
	public function __construct($conf, $link_conf = array())
	{
		if (class_exists('mysqli')) {
			$this->settings['MySqli'] = true;
			$this->settings += array(
				'method' => array(
					0 => 'fetch_all',
					1 => 'fetch_array',
					2 => 'fetch_column',
					'reset' => 'reset',
					'close' => 'free',
				),
				'fetch' => array(
					1 => MYSQLI_ASSOC,
					2 => MYSQLI_NUM,
					3 => MYSQLI_BOTH,
				),
			);
		} else if (class_exists('PDO')) {
			$this->settings['PDO'] = true;
			$this->settings += array(
				'method' => array(
					0 => 'fetchAll',
					1 => 'fetch',
					2 => 'fetchColumn',
					'reset' => 'closeCursor',
					'close' => 'closeCursor',
				),
				'fetch' => array(
					1 => PDO::FETCH_ASSOC,
					2 => PDO::FETCH_NUM,
					3 => PDO::FETCH_BOTH,
				)
			);
		}
		if (empty($link_conf)) $link_conf = $this->settings['conf'];
		foreach($this->settings['conf'] as $k=>$v){
			if(!empty($conf[$k])) $link_conf[$k] = $conf[$k];
		}
		$this->conf = $link_conf + $conf;
		if ($this->settings['MySqli']) {
			$link = new mysqli($link_conf['host'], $conf['user'], $conf['pw'], $link_conf['dbname'], $link_conf['port']);
			if (!empty($link)) $link->set_charset($link_conf['charset']);
		} else if ($this->settings['PDO']) {
			$link = new PDO('mysql:' . http_build_query($link_conf, '', ';'), $conf['user'], $conf['pw'], array(
				PDO::ATTR_TIMEOUT => 1,
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				//PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.$link_conf['charset'],
			));
		} else {
			throw new \Exception('you must open the extension=pdo_mysql.dll;/extension=mysqli.dll;');
		}
		if (empty($link)) throw new \Exception('MySql can\'t open!');
		$this->link = $link;
	}
	public function result($sth, $method = 0, $type = 1)
	{ 
		if (empty($sth)) return array();
		else if (is_array($sth)) $sth = $this->prepare(...$sth);
		if ($sth instanceof \mysqli_stmt) $result = $sth->get_result();
		else if ($sth instanceof \PDOStatement) $result = $sth;
		else return $sth;
		$data = array();
		if ($method != 2) $type = $this->settings['fetch'][$type];
		$this->sql_query_time(array(
			'rows' => $this->prepare_line($sth),
			'lastid' => $this->prepare_lastid()
		));
		if(!empty($result)){			
			$data =  $this->call_method(array($result, $this->settings['method'][$method]), $type);
			$this->prepare_close($result);
		}
		return $data;
	}
	public function result_fetch($sql, $param = array(), $type = 1)
	{
		return $this->result(array($sql, $param), 1, $type);
	}
	public function result_all($sql, $param = array(), $type = 1)
	{
		return $this->result(array($sql, $param), 0, $type);
	}
	public function result_first($sql, $param = array(), $index = 0)
	{
		return $this->result(array($sql, $param), 2, $index);
	}
	public function result_query($sql, $param = array(),$list=false)
	{
		$sth = $this->prepare($sql, $param);
		if ($sth instanceof \mysqli_stmt || $sth instanceof \PDOStatement){
			$this->sql_query_time(array(
				'rows' => $this->prepare_line($sth),
				'lastid' => $this->prepare_lastid()
			));
			if ($sth instanceof \mysqli_stmt)$this->prepare_close($sth->get_result());
		}
		else $sth = null;
		return current($this->query);
	}
	public function update($sql, $param)
	{
		return $this->result_query($sql, $param);
	}
	public function insert($sql, $param, $list = false)
	{
		return $this->result_query($sql, $param,$list);
	}
	public function prepare($sql, $param = array(), $list = false)
	{
		$link = $this->link;
		$this->time = microtime(1);
		$this->sql = $sql;
		$sth = $link->prepare($sql);
		if(!empty($param)&&is_array($param)){
			if (!empty($param[0])&&is_array($param[0])) {
				return $this->result_prepare_array($sth,$param);
			}elseif($list){
				return $this->result_prepare_array($sth,$param);
			}
		}else{
			$param = null;
		}
		$sth->execute($param);
		return $sth;
	}
	public function result_prepare_array($sth,$arr)
	{
		$result = [];
		foreach ($arr as $k => $v) {
			$sth->execute(is_array($v)?$v:array($v));
			$result_data = array(
				'rows' => $this->prepare_line($sth),
				'lastid' => $this->prepare_lastid()
			);
			$this->sql_query_time($result_data);
			$this->time = microtime(1);
			$result[] = $result_data;
			$this->prepare_reset($sth);
		}
		return $result;
		
	}
	public function exec($sql)
	{
		$link = $this->link;
		$this->time = microtime(1);
		$this->sql = $sql;
		if ($this->settings['MySqli']) {
			$sth = $this->query($sql);
			$rows = $this->prepare_line($sth);
			$id = $this->prepare_lastid();
			$this->prepare_close($sth);
		} else {
			$rows = $link->exec($sql);
			$id = $link->lastInsertId();
		}
		$result = array('rows' => $rows, 'lastid' => $id);
		$this->sql_query_time($result);
		return $result;
	}
	public function query($sql, $method = 0, $type = 1)
	{
		$link = $this->link;
		$this->time = microtime(1);
		$this->sql = $sql;
		$sth = $link->query($sql);
		if (is_int($method)) {
			return $this->result($sth, $method, $type);
		} else {
			if ($sth instanceof \mysqli_stmt) $result = $sth->get_result();
			else if ($sth instanceof \PDOStatement) $result = $sth;
			$this->prepare_close($result);
			$this->sql_query_time();
			return $result;
		}
	}
	public function multi_query($sql, $method = 0, $type = 1)
	{
		$link = $this->link;
		$this->time = microtime(1);
		$this->sql = $sql;
		$result = array();
		if (!is_int($method)) $method = 0;
		if ($method != 2) $type = $this->settings['fetch'][$type];
		if ($this->settings['MySqli']) {
			if ($link->multi_query($sql)) {
				do {
					if ($sth = $link->use_result()) {
						$result[] = array(
							'lastid' => $link->insert_id,
							'rows' => $link->affected_rows,
							'result' => $this->call_method(array($sth, $this->settings['method'][$method]), $type)
						);
						$sth->free();
					} else if ($link->affected_rows) {
						$result[] = array(
							'lastid' => $link->insert_id,
							'rows' => $link->affected_rows,
							'result' => NULL
						);
					}
				} while ($link->next_result());
			}
		} else {
			if ($sth = $link->query($sql)) {
				do {
					$result[] = array(
						'rows' => $sth->rowCount(),
						'lastid' => $link->lastInsertId(),
						'result' => $this->call_method(array($sth, $this->settings['method'][$method]), $type)
					);
				} while ($sth->nextRowset());
				$sth->closeCursor();
			}
		}
		$this->sql_query_time(array(
			'rows' => array_sum(array_column($result, 'rows')),
			'lastid' => $this->prepare_lastid()
		));
		return $result;
	}
	public function selectdb($dbname)
	{
		if ($this->settings['MySqli']) {
			return $this->link->select_db($dbname);
		} else {
			return $this->link->query('USE `' . $dbname . '`;');
		}
	}
	private function call_method($callee, ...$arg)
	{
		if (!is_callable($callee)) return array();
		return call_user_func_array($callee, $arg);
	}
	private function prepare_reset($sth)
	{
		return $this->call_method(array($sth, $this->settings['method']['reset']));
	}
	private function prepare_close($sth)
	{
		return $this->call_method(array($sth, $this->settings['method']['close']));
	}
	private function prepare_line($result)
	{
		$line = 0;
		if ($result instanceof \mysqli_stmt) $line = $result->affected_rows;
		else if ($result instanceof \mysqli_result) $line = $result->num_rows;
		else if ($result instanceof \PDOStatement) $line = $result->rowCount();
		return $line ?: 0;
	}
	private function prepare_lastid()
	{
		$line = 0;
		if ($this->settings['MySqli']) $line = $this->link->insert_id;
		else if ($this->settings['PDO']) $line = $this->link->lastInsertId();
		return $line ?: 0;
	}
	private function sql_query_time($param = array())
	{
		$this->query[] = array(
			'time' => round((microtime(1) - $this->time) * 1000, 2),
			'sql' => $this->sql
		) + $param;
	}
	public function format_table($table)
	{
		if (is_string($table)) return $this->conf['pre'] . $table;
		else if (is_array($table)) return array_map(fn ($v) => $this->format_table($v), $table);
	}
	public function __destruct()
	{
		if ($this->settings['MySqli']) $this->link->close();
	}
}
/**
 * 通用mysql数据库 静态接口
 */
class DB
{
	public static $_app;
	public array $tablelist = array();
	private array $settings = array(
		'map' => array(),
		'link' => array(),
		'prepare_base' => array(
			'table' => '', #设置查询表名
			'method' => 'SELECT', #设置操作类型 SELECT/INSERT/UPDATE/DELETE
			'FetchRow' => 1, #设置查询返回 1单行/0多行/2单行第一列
			'indexkey' => '', #设置查询返回 是否使用字段索引
			'FetchMode' => 0, //设置查询返回 字段是否数字化
			'where' => '', #设置查询条件
			'condition' => false, #查询条件 变为OR
			'select' => '', #设置查询字段
			'param' => [], //记录绑定后的查询参数
			'fetchColumn' => false, //在fetchColumn模式下是否返回多条数据,一旦设置countmode=0,FetchRow=2
			'update' => false, #设置插入数据时,是否有则更新否则插入
			'explain' => false, #优化器
			'sql' => '',
		),
		'prepare_condition' => array(
			'!=' => '<>',
			'!' => '<>',
			'~' => '<>',
			'NOT' => '<>',
			'NOT IN' => '<>',
			'IS NOT' => '<>',
			'IN' => '=',
			'==' => '=',
			'IS' => '<=>',
		),
	);
	function __construct($conf = array())
	{
		if (self::$_app) return;
		self::$_app = $this;
		if (!empty($conf)) $this->setConf($conf);
	}
	private function setConf($conf)
	{
		if (!empty($conf[1])) {
			$this->settings['conf'] = $conf[1];
		}
		if (!empty($conf['map'])) {
			foreach ($conf['map'] as $k => $v) {
				if (!empty($conf[$v])) {
					$this->settings['map'][$k] = $conf[$v];
					$this->settings['map'][$k]['id'] = $v;
				}
			}
		}
	}
	private function getConf($table)
	{
		return is_string($table) && isset($this->settings['map'][$table]) ? $this->settings['map'][$table] : $this->settings['conf'];
	}
	public function connect($table = false)
	{
		$conf = $this->getConf($table);
		$id = empty($conf['id']) ? 1 : $conf['id'];
		if (!empty($this->settings['link'][$id])) return $this->settings['link'][$id];
		//if ($table === false) unset($link_conf['dbname']);
		$link = new db_mysqli($conf);
		$this->settings['link'][$id] = $link;
		return $link;
	}
	public function prepare_exec($query)
	{
		$query += $this->settings['prepare_base'];
		$conf = $this->getConf($query['table']);
		$query['pre_table'] = $conf['pre'] . $query['table'];
		$query['method'] = strtoupper($query['method']);
		$query['quote_table'] = self::quote($query['pre_table'], 0);
		if ($query['method'] == 'SELECT') {
			$query = $this->prepare_filter_select($query);
		} else if ($query['method'] == 'DELETE') {
			$query['sql'] = $query['method'] . ' FROM' . $query['quote_table'];
		} else if ($query['method'] == 'UPDATE') {
			$query = $this->prepare_filter_update($query);
			$query['sql'] = $query['method'] . $query['quote_table'] . ' SET ' . $query['sql'];
		} else if ($query['method'] == 'INSERT') {
			unset($query['where']);
			$query = $this->prepare_filter_insert($query);
			$query['sql'] = 'INSERT INTO' . $query['quote_table'] . $query['sql'];
		} else if ($query['method'] == 'SHOW') {
			$query['sql'] = 'SHOW ' . $query['action'] . ' FROM' . $query['quote_table'];
		}
		if (!empty($query['where'])) {
			if (is_array($query['where'])) {
				$query = $this->prepare_filter_where($query);
			} else {
				$query['sql'] .= ' WHERE ' . $query['where'];
			}
		}
		if ($query['method'] == 'SELECT') {
			if (!empty($query['order'])) {
				$query = $this->prepare_filter_order($query);
			}
			if ($query['FetchRow'] != 0) $query['limit'] = 1;
			if ($query['FetchRow'] != 2) $query['FetchMode'] += 1;
			if (!empty($query['limit'])) {
				$query = $this->prepare_filter_limit($query);
			}
			if ($query['explain'] === true) {
				$query['sql'] = 'EXPLAIN ' . $query['sql'];
			}
		}
		$link = $this->connect($query['table']);
		$query['sql'] = trim($query['sql'],';').';';
		if ($query['method'] == 'UPDATE') {
			$result = $link->update($query['sql'], $query['param']);
		} else if ($query['method'] == 'INSERT') {
			$result = $link->insert($query['sql'], $query['param'], $query['list']);
		} else {
			$sth = $link->prepare($query['sql'], $query['param']);
			$result = $link->result($sth, $query['FetchRow'], $query['FetchMode']);
			if (!empty($query['indexkey'])&&!empty($result)) {
				$keys = array_column($result, $query['indexkey']);
				if (count($keys) == count($result)) $result = array_combine($keys, $result);
			}
		}
		return $result;
	}
	private function prepare_filter_where($query)
	{
		$table = $query['pre_table'];
		$where = $query['where'];
		$condition = $query['condition'];
		$cstr = $condition ? ' OR ' : ' AND ';
		$sql = '';
		foreach ($where as $key => $value) {
			$param_cdt = strstr($key, ':', true);
			if ($param_cdt !== false) {
				$param_name = str_replace($param_cdt . ':','',$key);
				$param_cdt = strtr($param_cdt, $this->settings['prepare_condition']);
			} else {
				$param_cdt = '=';
				$param_name = $key;
			}
			$quote_name = self::quote($param_name, 0, $table);
			if ($value === NULL || $value == 'NULL') {
				$sql .= $quote_name. ($param_cdt == '=' ? 'IS' : 'IS NOT') . ' NULL ' . $cstr;
				continue;
			}
			if (is_array($value)&&array_is_list($value)) {
				if (empty($value)) continue;
				$len = count($value);
				if ($len == 1) {
					$sql .= $quote_name . $param_cdt . ' ? ' . $cstr;
					$query['param'][] = $value[0];
				} else if (in_array($param_cdt, array('=', '<>'))) {
					if ($param_cdt == '=') $param_cdt = 'IN';
					elseif ($param_cdt == '<>') $param_cdt = 'NOT IN';
					$sqlx = '';
					foreach ($value as $b) {
						if ($b === NULL || $b == 'NULL') {
							$sqlx .= 'NULL,';
						} else {
							$sqlx .= '?,';
							$query['param'][] = $b;
						}
					}
					$sql .= self::quote($param_name, !1, $table) . $param_cdt . ' (' . rtrim($sqlx, ',') . ') ' . $cstr;
				} else {
					$sqlx = '';
					foreach ($value as $b) {
						if ($b === NULL || $b == 'NULL') {
							$sqlx .= $quote_name . $param_cdt . ' NULL  AND ';
						} else {
							$query['param'][] = $b;
							$sqlx .= $quote_name . $param_cdt . ' ?  AND ';
						}
					}
					$sql .= '( ' . rtrim($sqlx, ' AND ') . ' ) ' . $cstr;
				}
			} else{
				$sql .= $quote_name . $param_cdt . ' ? ' . $cstr;
				$query['param'][] = $value;
			}
		}
		if(!empty($sql)){
			$query['sql'] .= 'WHERE ' . rtrim($sql, $cstr);
			#print_r($sql);exit;
		}
		return $query;
	}
	private function prepare_filter_order($query)
	{
		if (is_array($query['order'])) {
			$sql = '';
			foreach ($query['order'] as $k => $v) {
				if (is_numeric($k)) {
					$sql .= self::quote($v, 0) . 'DESC,';
				} else {
					$quote_key = self::quote($k, 0); 
					if (!$v || $v == 'desc' || $v == 'DESC') {
						$sql .= $quote_key . 'DESC,';
					} else {
						$sql .= $quote_key . 'DESC,';
					}
				}
			}
			if (!empty($sql)) {
				$query['sql'] .= ' ORDER BY ' . rtrim($sql, ',');
			}
		}
		return $query;
	}
	public function prepare_filter_limit($query)
	{
		if (empty($query['limit'])) $query['limit'] = 1;
		else if (is_array($query['limit'])) $query['limit'] = implode(',', $query['limit']);
		$query['sql'] .= ' LIMIT ' . $query['limit'];
		return $query;
	}
	public function prepare_filter_select($query)
	{
		$sql = 'SELECT ';
		if (empty($query['select'])) $sql .= ' *';
		else {
			if (is_array($query['select'])) {
				$sql .= implode(',', self::quote($query['select'], 1));
			} else {
				$sql .= $query['select'];
			}
		}
		$query['sql'] = $sql . ' FROM' . $query['quote_table'] . $query['sql'];
		return $query;
	}
	public function prepare_filter_update($query)
	{

		$sql = '';
		foreach ($query['data'] as $k => $v) {
			if ($key = strstr($k, ':', !0)) {
				$k = str_replace($key . ':','',$k);
				$sql .= self::quote($k, 1) . '=' . self::quote($k, 1) . $key . '? ,';
			} else {
				$sql .= self::quote($k, 1) . '= ? ,';
			}
			$query['param'][] = is_array($v) ? serialize($v) : $v;
		}
		$query['sql'] = rtrim($sql, ',');
		return $query;
	}
	public function prepare_filter_insert($query)
	{

		$query['list'] = false;
		if (array_is_list($query['data'])) {
			$query['list'] = true;
			$keys = array_shift($query['data']);
			$query['param'] = $query['data'];
		} else {
			$keys = array_keys($query['data']);
			$query['param'] = array_values($query['data']);
		}
		$sql = '(' . implode(',', self::quote($keys, !1)) . ') VALUES (' . rtrim(str_repeat('?,', count($keys)), ',') . ')';
		if ($query['update']) {
			$sql .= ' AS' . self::quote('newdata', !1) . 'ON DUPLICATE KEY UPDATE ' . implode(',', array_map(fn ($v) => self::quote($v, 1) . '=' . self::quote($v, 1, 'newdata'), $keys)) . ' ';
		}
		$query['sql'] = $sql;
		return $query;
	}
	public static function quote($str, $space = false, $pre = '')
	{
		if (is_array($str)) return array_map(fn ($m) => self::quote($m, 1, $pre), $str);
		$space = $space ? '' : ' ';
		if (!empty($pre)) $pre = '`' . $pre . '`.';
		return $space . $pre . '`' . trim($str) . '`' . $space;
	}
	public static function quote2($str, $space = false, $pre = '')
	{
		if (is_array($str)) return array_map(fn ($m) => self::quote2($m, 1, $pre), $str);
		$space = $space ? '' : ' ';
		if (!empty($pre)) $pre = '\'' . $pre . '\'.';
		return $space . $pre . '\'' . trim($str) . '\'' . $space;
	}
	public static function app($conf = array())
	{
		if (empty(self::$_app)) {
			$class = get_called_class();
			return new $class($conf);
		};
		return self::$_app;
	}
	public static function FetchOne($table, $where = '', $query = array())
	{
		return self::app()->prepare_exec($query + array('table' => $table, 'where' => $where)) ?: array();
	}
	public static function FetchAll($table, $where = '', $query = array())
	{
		return self::app()->prepare_exec($query + array('table' => $table, 'where' => $where, 'FetchRow' => 0)) ?: array();
	}
	public static function FetchColumn($table, $where = '', $query = array())
	{
		return self::app()->prepare_exec($query + array('table' => $table, 'where' => $where, 'FetchRow' => 2)) ?: array();
	}
	public static function Rows($table)
	{
		$link = self::getLink($table);
		if (is_string($table)) {
			$table = explode(',', $table);
		}
		$result = $link->query('EXPLAIN SELECT count(*) FROM ' . implode(',', self::quote(array_map(fn ($v) => $link->conf['pre'] . $v, $table), !1, $link->conf['dbname'])) . ';');
		$rows = [];
		foreach ($result as $k => $v) {
			$rows[$table[$k]] = $v['rows'];
		}
		return $rows;
	}
	public static function TableField($table = '', $quote = true)
	{
		$link = self::getLink();
		$dbname = $link->conf['dbname'];
		$pre = $link->conf['pre'];
		$sql = 'SELECT `TABLE_NAME`,`COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` = ? ';
		$len = 0;
		if (!empty($table)) {
			if (is_string($table)) {
				$table = explode(',', $table);
			}
			$len = count($table);
			$where = $quote ? (array_map(fn ($v) => $pre . $v, $table)) : $table;
			array_unshift($where, $dbname);
			$sql .= ' AND `TABLE_NAME` ' . ($len > 1 ? 'IN (' . rtrim(str_repeat('?,', count($where) - 1), ',') . ')' : ' = ? ') . ';';
		} else {
			$where = array($dbname);
		}
		$result = $link->result(array($sql, $where));
		$field = [];
		foreach ($result as $k => $v) {
			$t = str_replace($pre, '', $v['TABLE_NAME']);
			$field[$t][] = $v['COLUMN_NAME'];
		}
		if ($len == 1) return $field[$table[0]];
		return $field;
	}
	public static function DBField()
	{
		$link = self::getLink();
		$dbname = $link->conf['dbname'];
		$result = $link->result(array('SELECT `TABLE_NAME` FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = ? ;', array($dbname)));
		$field = [];
		foreach ($result as $k => $v) {
			$field[] = $v['TABLE_NAME'];
		}
		return $field;
	}
	public static function update($table, $data, $where = '', $query = array())
	{
		$query['method'] = 'UPDATE';
		$query['table'] = $table;
		$query['data'] = $data;
		$query['where'] = $where;
		$query['FetchRow'] = 2;
		$result = self::app()->prepare_exec($query);
		return !empty($result['rows']) ? $result['rows'] : 0;
	}
	public static function insert($table, $data, $update = false, $query = array())
	{
		$query['method'] = 'INSERT';
		$query['table'] = $table;
		$query['data'] = $data;
		$query['FetchRow'] = 2;
		$query['update'] = $update;
		$result = self::app()->prepare_exec($query);
		return $result;
		//!empty($result['lastid']) ?$result['rows']: 0;
	}
	public static function getLink($table = false)
	{
		return self::app()->connect($table);
	}
	public static function mquery($sql, $method = 0, $type = 1)
	{
		$link = self::getLink();
		return $link->multi_query($sql, $method, $type);
	}
	public static function mquery_table($table)
	{
		if (is_string($table)) $table = array($table);
		$link = self::getLink();
		$sql = implode(';', array_map(fn ($v) => 'SELECT * FROM ' . $link->format_table($v), $table)) . ';';
		$result = self::mquery($sql);
		$newresult = [];
		foreach ($table as $k => $v) {
			if (isset($result[$k]) && !empty($result[$k]['result'])) {
				$newresult[$v] = $result[$k]['result'];
			}
		}
		return $newresult;
	}
	public static function query($sql, $method = 0, $type = 1)
	{
		$link = self::getLink();
		return $link->query($sql, $method, $type);
	}
	public static function exec($sql)
	{
		$link = self::getLink();
		return $link->query($sql);
	}
	public static function t($table, $plugin = '')
	{
		$db = self::app();
		if (!empty($db->tablelist[$table])) return $db->tablelist[$table];
		$class = 'Nenge\\table\\table_' . $table;
		if (!class_exists($class, !1)) {
			if (!empty($plugin)) {
				$path = APP::app()->data['path']['plugin']. $plugin . '/table/';
			} else {
				$path =  __DIR__. '\\table\\';
			}
			include_once $path . $table . '.php';
			$db->tablelist[$table] = new $class;
		}
		return $db->tablelist[$table];
	}
	public static function p($str)
	{
		if (is_string($str)) $str = explode(':', $str);
		list($plugin, $table) = $str;
		return self::t($table, $plugin);
	}
	public static function getSql()
	{
		$db = self::app();
		if (!empty($db->settings['link'])) {
			$sql = [];
			foreach ($db->settings['link'] as $k => $v) {
				$sql[] = $v->query;
			}
			return  array_merge(...$sql);
		}
	}
}


namespace Nenge\table;
use \Nenge\DB;
/**
 * table类 数据出来基础
 */
class base
{
	public string $table;
	public string $indexkey;
	public function getdb()
	{
		return DB::app();
	}
	public function connect()
	{
		return DB::getLink($this->table);
	}
	public function tablename()
	{
		return $this->connect()->format_table($this->table);
	}
	public function dbname()
	{
		return $this->connect()->conf['dbname'];
	}
	public function quote_table($table='',$dbname='')
	{
		if(empty($table)) $table = $this->tablename();
		if(empty($dbname)) $dbname = $this->dbname();
		return DB::quote($table,!0,$dbname);
	}
	public function quote_field($field,$table=false)
	{
		if(!$table)$table = $this->tablename();
		return DB::quote($field,!0,$this->tablename());
	}
	public function param($query)
	{
		if (!empty($this->indexkey) && !isset($query['indexkey'])) {
			$query['indexkey'] = $this->indexkey;
		}
		return $query;
	}
	public function fetch($where = '', $query = array())
	{
		return DB::FetchOne($this->table, $where, $this->param($query));
	}
	public function all($where = '', $query = array())
	{
		return DB::FetchAll($this->table, $where, $this->param($query));
	}
	public function column($where = '', $query = array())
	{
		return DB::FetchColumn($this->table, $where, $this->param($query));
	}
	public function field()
	{
		return DB::TableField($this->table);
	}
	public function fieldAttr()
	{
		$link = $this->connect();
		$result = $link->result(array('SELECT * FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` = ? AND `TABLE_NAME`= ? ;', array($link->conf['dbname'], $this->tablename())));
		return $result;
	}
	public function insert($data, $update = false, $query = array())
	{
		return DB::insert($this->table, $data, $update, $query);
	}
	public function update($data, $where = '', $query = array())
	{
		return DB::update($this->table, $data, $where, $query);
	}
	public function rows($where = '', $key = false)
	{
		if (!$key) $key = $this->indexkey;
		return DB::FetchColumn($this->table, $where, array('select' => 'count(`' . $key . '`)', 'FetchMode' => 0));
	}
	public function rows_count()
	{
		return $this->connect()->result_first('SELECT count(*) FROM '.$this->quote_table().';');
	}
	public function rows_by_exp($where = '', $key = false)
	{
		if (!$key) $key = $this->indexkey;
		return DB::FetchColumn($this->table, $where, array('select' => 'count(`' . $key . '`)', 'explain' => !0, 'FetchMode' => 9));
	}
	public function rows_by_table()
	{
		$link = $this->connect();
		$result =  $link->result(array(
			'SELECT * FROM `information_schema`.`TABLES` WHERE `TABLE_NAME`= ?;',
			array($this->tablename())
		), 1, 1);
		if (!empty($result)) return $result['TABLE_ROWS'];
		return 0;
	}
	public function index()
	{
		return DB::FetchColumn($this->table, '', array(
			'method' => 'SHOW',
			'action' => 'INDEX',
			'FetchMode' => 4
		));
	}
	public function rand($limit = 1, $where = '', $fetchmethod = 0, $fetchmode = 0)
	{
		$link = $this->connect();
		if ($limit == 1 && $fetchmethod != 2) $fetchmethod = 1;
		if ($fetchmethod != 2) $fetchmode += 1;
		if (!empty($where)) $where = ' WHERE ' . $where;
		return $link->result(
			array(
				'SELECT * FROM ' . $this->quote_table() . $where . ' ORDER BY RAND() LIMIT ' . $limit . ';'
			),
			$fetchmethod,
			$fetchmode
		);
	}
	public function rand_by_id($where = '', $indexkey = false, $fetchmethod = 1, $fetchmode = 0)
	{
		if (!$indexkey) $indexkey = $this->indexkey;
		$link = $this->connect();
		if ($fetchmethod != 2) $fetchmode += 1;
		$table = $this->quote_table();
		if (!empty($where)) $where = ' AND (' . $where . ')';
		$t1 = $this->quote_field($indexkey,'t1');
		return $link->result(
			array(
				'SELECT * FROM ' . $table . ' AS `t1` JOIN (SELECT ROUND(RAND() * ((SELECT MAX(`' . $indexkey . '`) FROM ' . $table . ')-(SELECT MIN(`' . $indexkey . '`) FROM ' . $table . '))+(SELECT MIN(`' . $indexkey . '`) FROM ' . $table . ')) AS `randid`) AS `t2` WHERE ' . $t1. ' >= ' .$this->quote_field('randid','t2'). ' ORDER BY ' . $t1 . ' LIMIT 1;'
			),
			$fetchmethod,
			$fetchmode
		);
	}
}
