<?php
class MyDB
{
	/**
	 * 主链接
	 * @var MySQL|MyPDO
	 */
	public MySQL|MyPDO $wlink;  // 写连接
	/**
	 * 从链接
	 *
	 * @var MySQL|MyPDO
	 */
	public MySQL|MyPDO $rlink;  // 读连接
	/**
	 * 主配置
	 *
	 * @var array
	 */
	public $conf = array(); // 配置，可以支持主从
	/**
	 * 从配置
	 * @var array
	 */
	public $rconf = array(); // 配置，可以支持主从
	public $sqls = array();
	public $scheme = '';
	public $tablepre = '';
	public $errno = 0;
	public $errstr = '';
	public $innodb_first; // 优先 InnoDB
	public array $tables = array();
	public static $_db;
	/**
	 * 返回最后插入的主键值  
	 * 即 insert_id
	 */
	const MODE_INSERT_ID  = -1;
	/**
	 * 返回数据更新的影响行数
	 */
	const MODE_ROWS_VALUE  = 0;
	/**
	 * 返回带索引字段的多行数据
	 */
	const MODE_ALL_ASSOC = 1;
	/**
	 * 返回带数字索引的多行数据
	 */
	const MODE_ALL_NUM = 2;
	/**
	 * 返回混合索引的多行数据
	 */
	const MODE_ALL_BOTH = 3;
	/**
	 * 返回带字段索引的单行数据
	 */
	const MODE_ASSOC = 4;
	/**
	 * 返回带数字索引的单行数据
	 */
	const MODE_NUM = 5;
	/**
	 * 返回带混合索引的单行数据
	 */
	const MODE_BOTH = 6;
	/**
	 * 返回单行首列数据
	 */
	const MODE_COLUMN_VALUE = 7;
	/**
	 * 以迭代器形式返回多行数据  
	 * 处理超大量文本数据时,务必采用
	 */
	const MODE_ITERATOR = 10;
	public function __construct(array $conf, ?string $scheme = null)
	{
		self::$_db = $this;
		$this->setConfig($conf, $scheme);
	}
	public static function app()
	{
		if (!isset(self::$_db)):
			throw new Error('数据驱动尚未初始化');
		endif;
		return self::$_db;
	}
	public static function t(string $table, ?string $key = null): MyTable
	{
		$db = self::app();
		if (!isset($db->tables[$table])):
			$arr = explode('.', $table);
			$tablename = array_pop($arr);
			$dbname = array_pop($arr);
			$db->tables[$table] = new MyTable($dbname ? $tablename : $db->tablepre . $tablename, $key, $dbname);
		endif;
		return $db->tables[$table];
	}
	/**
	 * 从链接
	 */
	public static function rlink()
	{
		return self::app()->rlink ?? self::app()->connect_slave();
	}
	/**
	 * 从配置
	 */
	public static function rconf(string $name = ''): mixed
	{
		if (!isset(self::app()->rlink)):
			self::app()->connect_slave();
		endif;
		if (!empty($name)):
			return self::app()->rconf[$name] ?? '';
		endif;
		return self::app()->rconf;
	}
	/**
	 * 主链接
	 */
	public static function wlink()
	{
		return self::app()->wlink ?? self::app()->connect_master();
	}
	public function setConfig(array $conf, ?string $scheme = null)
	{
		$this->conf = $conf;
		$this->tablepre = $conf['master']['tablepre'];
		if (!empty($scheme)):
			$this->scheme = $scheme;
		endif;
	}
	// 根据配置文件连接
	public function connect()
	{
		$this->wlink = $this->connect_master();
		$this->rlink = $this->connect_slave();
		return $this->wlink && $this->rlink;
	}
	// 连接写服务器
	public function connect_master()
	{
		if (isset($this->wlink)) return $this->wlink;
		$this->wlink = $this->real_connect($this->conf['master']);
		return $this->wlink;
	}
	// 连接从服务器，如果有多台，则随机挑选一台，如果为空，则与主服务器一致。
	public function connect_slave()
	{
		if (isset($this->rlink)):
			return $this->rlink;
		endif;
		if (empty($this->conf['slaves'])):
			if (!isset($this->wlink)):
				$this->connect_master();
			endif;
			$this->rlink = &$this->wlink;
			$this->rconf = $this->conf['master'];
		else:
			$n = array_rand($this->conf['slaves']);
			$conf = $this->conf['slaves'][$n];
			$this->rconf = $conf;
			$this->rlink = $this->real_connect($conf);
		endif;
		return $this->rlink;
	}
	public function real_connect($conf)
	{
		if (empty($this->scheme)):
			return new MySQL($conf);
		else:
			return new MyPDO($conf, $this->scheme);
		endif;
	}
	public function error(MyPDO|MySQL $link)
	{
		if (!empty($link->errorCode())):
			if (defined('DEBUG') && DEBUG):
				trigger_error('Database Error:' . $link->errorMessage());
			endif;
			$this->errno = $link->errorCode();
			$this->errstr = $link->errorMessage();
		endif;
	}
	public function close()
	{
		if (isset($this->wlink)):
			if ($this->wlink instanceof MySQL):
				$this->wlink->close();
				$this->rlink->close();
			else:
				unset($this->wlink, $this->rlink);
			endif;
		endif;
	}
	public static function query($sql, $mode = self::MODE_ALL_ASSOC)
	{
		return self::rlink()->querySQL($sql, $mode);
	}
	public static function execute(string $query, array $param, int $mode = self::MODE_ALL_ASSOC): mixed
	{
		return self::rlink()->executeSQL($query, $param, $mode);
	}
	public static function exec($sql, $mode = self::MODE_ROWS_VALUE)
	{
		$sql = trim($sql);
		$wlink = self::wlink();
		if (strtoupper(substr($sql, 0, 12) == 'CREATE TABLE')):
			if (self::engine() != 'myisam'):
				$sql = str_ireplace('MyISAM', 'InnoDB', $sql);
			endif;
		endif;
		$pre = strtoupper(substr(trim($sql), 0, 7));
		if ($pre == 'INSERT ' || $pre == 'REPLACE') {
			$mode = self::MODE_INSERT_ID;
		} elseif ($pre == 'UPDATE ' || $pre == 'DELETE ') {
			$mode = self::MODE_ROWS_VALUE;
		}
		return $wlink->execSQL($sql, $mode);
	}
	public function sql_find_one($sql)
	{
		return $this->query($sql,self::MODE_ASSOC) ?: false;
	}

	public function sql_find($sql, $key = NULL)
	{
		$result =  $this->query($sql, 1) ?: false;
		if (!empty($key) && !empty($result)):
			return array_column($result, null, $key);
		endif;
		return $result;
	}
	/**
	 * @deprecated version 4.1 废弃函数
	 */
	public function find($table, $cond = array(), $orderby = array(), $page = 1, $pagesize = 10, $key = '', $col = array())
	{
		$page = max(1, $page);
		$orderby = self::xn_sql_order($orderby);
		$offset = ($page - 1) * $pagesize;
		$cols = $col ? implode(',', $col) : '*';
		$where = self::xn_sql_where($cond);
		$result =  self::rlink()->executeSQL('SELECT ' . $cols . ' FROM ' . self::tableqoute($table) . $where[0] . $orderby . ' LIMIT ' . $offset . ',' . $pagesize . PHP_EOL, $where[1]);
		if (!empty($key) && isset($result[0]) && isset($result[0][$key])):
			return array_column($result, null, $key);
		endif;
		return $result;
	}
	/**
	 * @deprecated version 4.1 废弃函数
	 */
	public function find_one($table, $cond = array(), $orderby = array(), $col = array())
	{
		$orderby = self::xn_sql_order($orderby);
		$cols = $col ? implode(',', $col) : '*';
		$where = self::xn_sql_where($cond);
		return self::rlink()->executeSQL('SELECT ' . $cols . ' FROM ' . self::tableqoute($table) . $where[0] . $orderby . ' LIMIT 1', $where[1], 4);
	}
	public static function version()
	{
		return self::rlink()->serverVersion();
	}
	public function is_support_innodb()
	{
		$arrlist = array_column($this->query('SHOW ENGINES'), 'Support', 'Engine');
		return isset($arrlist['InnoDB']) && $arrlist['InnoDB'] != 'NO';
	}
	public static function engine()
	{
		return self::app()->conf['master']['engine'];
	}
	public static function truncate($table)
	{
		return self::exec("TRUNCATE $table");
	}
	public static function maxid(string $table, string $field, array $cond = array())
	{
		$where = self::xn_sql_where($cond);
		return self::rlink()->executeSQL('SELECT MAX(' . $field . ') FROM ' . self::quote($table) . ' ' . $where[0], $where[1], 7);
	}
	// 如果为 innodb，条件为空，并且有权限读取 information_schema
	/**
	 * 行数
	 */
	public static function count($table, $cond = array())
	{
		$rconf = self::app()->rconf;
		if (empty($cond) && $rconf['engine'] == 'innodb'):
			return self::rlink()->executeSQL('SELECT `TABLE_ROWS` FROM `information_schema`.`tables` WHERE `TABLE_SCHEMA`=? AND `TABLE_NAME`=?', array(self::rconf('name'), self::tablename($table)), 7);
		else:
			$where = self::xn_sql_where($cond);
			return self::rlink()->executeSQL('SELECT COUNT(*) FROM ' . self::tableqoute($table) . ' ' . $where[0], $where[1], 7);
		endif;
	}
	/**
	 * 修罗旧有where条件处理函数
	 */
	public static function xn_sql_where(mixed $cond): array
	{
		$s = '';
		$param = [];
		if (!empty($cond) && is_array($cond)):
			$s = ' WHERE ';
			foreach ($cond as $k => $v):
				if (!is_array($v)):
					$s .= self::quote($k) . '= ? AND ';
					$param[] = $v;
				elseif (isset($v[0])):
					// OR 效率比 IN 高
					$s .= '(';
					//$v = array_reverse($v);
					foreach ($v as $v1):
						$s .= self::quote($k) . '= ? OR ';
						$param[] = $v1;
					endforeach;
					$s = substr($s, 0, -4);
					$s .= ') AND ';
				else:
					foreach ($v as $k1 => $v1):
						if ($k1 == 'LIKE'):
							$k1 = ' LIKE ';
							$v1 = '%' . $v1 . '%';
						endif;
						$s .= self::quote($k) . $k1 . ' ? AND ';
						$param[] = $v1;
					endforeach;
				endif;
			endforeach;
			$s = substr($s, 0, -4);
			return [$s, $param];
		elseif (empty($cond)):
			$cond = '';
		endif;
		return [$cond, []];
	}
	/**
	 * 修罗旧有 更新处理函数
	 */
	public static function xn_sql_update(array $arr, array $where = array())
	{
		$s = [];
		$param = array();
		foreach ($arr as $k => $v):
			$param[] = $v;
			$op = substr($k, -1);
			if ($op == '+' || $op == '-') {
				$k = substr($k, 0, -1);
				$s[] = $op == '+' ? self::KEY_ADD($k) : self::KEY_PDD($k);
			} else {
				$s[] = self::KEY_SET($k);
			}
		endforeach;
		$s = implode(',', $s);
		if (!empty($where)):
			$w = self::xn_sql_where($where);
			$s .= $w[0];
			array_push($param, ...$w[1]);
		endif;
		return array($s, $param);
	}
	/**
	 * 修罗旧有 插入数据处理函数
	 */
	public static function xn_sql_insert($arr = array())
	{
		$keys = array();
		$values = array();
		$param = array();
		foreach ($arr as $k => $v):
			$keys[] = self::quote($k);
			$values[] = '?';
			$param[] = $v;
		endforeach;
		$keystr = implode(',', $keys);
		$valstr = implode(',', $values);
		$sql = ' (' . $keystr . ') VALUES (' . $valstr . ') ';
		return [$sql, $param];
	}
	/**
	 * 修罗旧有 order处理函数
	 */
	public static function xn_sql_order($orderby = array())
	{
		$s = '';
		if (!empty($orderby)):
			$s .= ' ORDER BY ';
			$comma = '';
			foreach ($orderby as $k => $v):
				$s .= $comma . self::quote($k) . ' ' . ($v == 1 ? ' ASC ' : ' DESC ');
				$comma = ',';
			endforeach;
		endif;
		return $s;
	}
	public static function sql_select(string $name, $column = '*')
	{
		$column = self::quote($column, true);
		return 'SELECT ' . $column . ' FROM ' . $name . ' ';
	}
	public static function sql_delete(string $name)
	{
		return 'DELETE FROM ' . $name . ' ';
	}
	public static function sql_update(string $name)
	{
		return 'UPDATE ' . $name . ' SET ';
	}
	public static function sql_insert(string $name)
	{
		return 'INSERT INTO ' . $name . ' ';
	}
	public static function sql_replace(string $name)
	{
		return 'REPLACE INTO ' . $name . ' ';
	}
	public static function sql_alert(string $name)
	{
		return 'ALTER TABLE ' . $name . ' ';
	}
	/**
	 * `key`,[`key`]...
	 * @param string|array $column select会以,链接,查询字段会以.链接
	 * @param boolean $bool 是否select 头,否则是查询字段
	 * @return string
	 */
	static public function quote(string|array $column, $bool = false): string
	{
		if (empty($column)):
			return $bool ? '*' : '';
		endif;
		if (is_array($column)):
			$column = array_filter($column, fn($m) => !empty($m));
			$column = array_map(fn($m) => self::quote($m, $bool), $column);
			if ($bool):
				return implode(',', $column);
			else:
				return implode('.', $column);
			endif;
		endif;
		$column = trim($column);
		if ($column == '*'):
			return $column;
		endif;
		if ($bool):
			switch (true):
				case str_contains($column, '`'):
				case str_ends_with($column, ')'):
				case str_contains($column, ' as '):
				case str_contains($column, ' AS '):
					return $column;
					break;
			endswitch;
		endif;
		if (str_contains($column, '.')):
			return self::quote(explode('.', $column), false);
		endif;
		if (str_starts_with($column, '`')):
			return $column;
		endif;
		return '`' . $column . '`';
	}
	/**
	 * `key` IN (?,?,?)
	 */
	static public function KEY_IN(string $key, int $len = 1): string
	{
		if ($len == 1):
			return self::KEY_SET($key);
		endif;
		return self::quote($key) . ' IN (' . substr(str_repeat(',?', $len), 1) . ') ';
	}
	/**
	 * `key` = ?
	 */
	static public function KEY_SET(string $key): string
	{
		return self::quote($key) . ' = ? ';
	}
	static function KEY_LIKE(string $key): string
	{
		return self::quote($key) . ' LIKE ? ';
	}
	static function KEY_NOT_LIKE(string $key): string
	{
		return self::quote($key) . ' NOT LIKE ? ';
	}
	/**
	 * `key` IS NULL
	 */
	static public function KEY_NULL(string $key): string
	{
		return self::quote($key) . ' IS NULL ';
	}
	/**
	 * `key` NOT IN (?,?,?)
	 */
	static public function KEY_NOT_IN(string $key, int $len = 1): string
	{
		if ($len == 1):
			return self::KEY_NOT($key);
		endif;
		return self::quote($key) . ' NOT IN (' . substr(str_repeat(',?', $len), 1) . ') ';
	}
	/**
	 * `key` <> ?
	 */
	static public function KEY_NOT(string $key): string
	{
		return self::quote($key) . ' <> ?';
	}
	/**
	 * `key` IS NOT NULL
	 */
	static public function KEY_NOT_NULL(string $key): string
	{
		return self::quote($key) . ' IS NOT NULL';
	}
	/**
	 * `key` > ?
	 */
	static public function KEY_THEN(string $key): string
	{
		return self::quote($key) . ' > ? ';
	}
	/**
	 * `key` < ?
	 */
	static public function KEY_LESS(string $key): string
	{
		return self::quote($key) . ' < ? ';
	}
	/**
	 * `key` = `key`+ ?
	 */
	static public function KEY_ADD(string $key): string
	{
		return self::quote($key) . '=' . self::quote($key) . ' + ?';
	}
	/**
	 * `key` = `key` - ?
	 */
	static public function KEY_PDD(string $key): string
	{
		return self::quote($key) . '=' . self::quote($key) . ' - ?';
	}
	static public function KEY_IF_PDD(string $key, int $defalut = 0): string
	{
		return self::quote($key) . '=IF(' . self::KEY_THEN($key) . ',' . self::quote($key) . '-?,' . $defalut . ')';
	}
	static public function KEY_IF_ADD(string $key, int $defalut = 1)
	{
		return self::quote($key) . '=IF(' . self::KEY_LESS($key) . ',' . self::quote($key) . ' + ' . $defalut . ',?)';
	}
	/**
	 * `key` = `key` - ?
	 */
	static public function KEY_RP(string $key): string
	{
		return self::quote($key) . '=REPLACE(' . self::quote($key) . ',?,?)';
	}
	static public function KEY_FILE(string $key): string
	{
		return self::quote($key) . ' = LOAD_FILE(?)';
	}
	static public function KEY_ORDER(string|array $key, string $order = 'DESC'): string
	{
		if (is_array($key)):
			return implode(',', array_map(fn($k, $v) => self::KEY_ORDER($k, $v), array_keys($key), array_values($key)));
		endif;
		return self::quote($key) . ' ' . (strtoupper($order) == 'ASC' ? 'ASC' : 'DESC');
	}
	/**
	 * array<`key` = ?>
	 */
	static public function paramSet(array $field): array
	{
		return array_map(fn($key) => self::KEY_SET($key), $field);
	}
	static public function paramLike(array $field): array
	{
		return array_map(fn($key) => self::KEY_LIKE($key), $field);
	}
	/**
	 * array<`key` <> ?>
	 */
	static public function paramNotEq(array $field): array
	{
		return array_map(fn($key) => self::KEY_NOT($key), $field);
	}
	/**
	 *  ?,?,?
	 */
	static public function fill(array|int $value = 1): string
	{
		if (is_array($value)):
			$value = count($value);
		endif;
		return substr(str_repeat(',?', $value ?: 1), 1);
	}
	/**
	 * 一维数据AND 链接
	 * array<$sql,$param,...mixed>
	 */
	static public function WHERE_AND(array $where, string $endsql = '', ...$arg): array
	{
		list($newwhere, $param) = self::WHERE_VALUE($where);
		if (empty($newwhere)):
			array_unshift($arg, $endsql, []);
			return $arg;
		endif;
		array_unshift($arg, PHP_EOL . 'WHERE ' . implode(' AND ', $newwhere) . ' ' . $endsql, $param);
		return $arg;
	}
	/**
	 * 一维数据OR链接
	 */
	static public function WHERE_OR(array $where, string $endsql = '', ...$arg): array
	{
		list($newwhere, $param) = self::WHERE_VALUE($where);
		if (empty($newwhere)):
			array_unshift($arg, $endsql, []);
			return $arg;
		endif;
		array_unshift($arg, PHP_EOL . 'WHERE ' . implode(' OR ', $newwhere) . ' ' . $endsql, $param);
		return $arg;
	}
	/**
	 * 二维数据,用AND链接,一维数据用OR
	 */
	static public function WHERE_WITH(array $data, string $endsql = '', ...$arg)
	{
		$sql = [];
		$param = array();
		foreach ($data as $where):
			list($newwhere, $newparam) = self::WHERE_VALUE($where);
			if (!empty($newwhere)):
				if (count($newwhere) == 1):
					$sql[] = array_pop($newwhere);
				else:
					$sql[] = '(' . implode(' OR ', $newwhere) . ')';
				endif;
				array_push($param, ...$newparam);
			endif;
		endforeach;
		array_unshift($arg, PHP_EOL . 'WHERE ' . implode(' AND  ', $sql) . ' ' . $endsql, $param);
		return $arg;
	}
	/**
	 * 配置WHERE查询 索引可用前缀 !<>% 代码不等于,小于,大于,LIKE
	 * @param array $data
	 * @return array<array,array>
	 */
	static public function WHERE_VALUE(array $data): array
	{
		$where = array();
		$param = array();
		foreach ($data as $key => $v):
			$char = substr($key, 0, 1);
			if (!ctype_alpha($char)):
				$key = substr($key, 1);
			else:
				$char = null;
			endif;
			$len = 1;
			if (is_array($v)):
				$len = count($v);
				if ($len == 1):
					$v = array_pop($v);
				elseif (empty($len)):
					$v = null;
				endif;
			elseif (is_null($v)):
				$len = 0;
			endif;
			if (!empty($char)):
				switch ($char):
					case '!':
						if ($len > 1):
							$where[] = self::KEY_NOT_IN($key, $len);
							array_push($param, ...$v);
							continue 2;
						elseif ($len === 1):
							$where[] = self::KEY_NOT($key);
							$param[] = $v;
							continue 2;
						else:
							$where[] = self::KEY_NOT_NULL($key);
							continue 2;
						endif;
						break;
					case '%':
						if ($len > 1):
							$where[] = '(' . substr(str_repeat(' OR ' . self::KEY_LIKE($key), $len), 3) . ')';
							array_push($param, ...array_map(fn($m) => '%' . $m . '%', $v));
							continue 2;
						elseif ($len === 1):
							$where[] = self::KEY_LIKE($key);
							$param[] = '%' . $v . '%';
							continue 2;
						endif;
						break;
					case '>':
						#仅支持数字
						if (is_numeric($v)):
							$where[] = self::KEY_THEN($key);
							$param[] = floatval($v);
							continue 2;
						endif;
						break;
					case '<':
						#仅支持数字
						if (is_numeric($v)):
							$where[] = self::KEY_LESS($key);
							$param[] = floatval($v);
							continue 2;
						endif;
						break;
					case '~':
						if ($len > 1):
							$where[] = '(' . substr(str_repeat(' AND ' . self::KEY_NOT_LIKE($key), $len), 4) . ')';
							array_push($param, ...$v);
							continue 2;
						elseif ($len === 1):
							$where[] = self::KEY_NOT_LIKE($key);
							$param[] = $v;
							continue 2;
						else:
							$where[] = self::KEY_NOT_NULL($key);
							continue 2;
						endif;
						break;
				endswitch;
			endif;
			if ($v === null):
				$where[] = self::KEY_NULL($key);
			elseif ($len > 1):
				$where[] = self::KEY_IN($key, $len);
				array_push($param, ...$v);
			elseif ($len == 1):
				$where[] = self::KEY_SET($key);
				$param[] = $v;
			endif;
		endforeach;
		return array($where, $param);
	}
	/**
	 * 配置WHERE查询 索引可用前缀 !<>% 代码不等于,小于,大于,LIKE
	 * @param array $data
	 * @return array<string>
	 */
	static public function WHERE_KEY(array $data): array
	{
		$where = array();
		foreach ($data as $key):
			$char = substr($key, 0, 1);
			if (!ctype_alpha($char)):
				$key = substr($key, 1);
			endif;
			switch ($char):
				case '!':
					$where[] = self::KEY_NOT($key);
					break;
				case '~':
					$where[] = self::KEY_NOT_LIKE($key);
					break;
				case '%':
					$where[] = self::KEY_LIKE($key);
					break;
				case '>':
					$where[] = self::KEY_THEN($key);
					break;
				case '<':
					$where[] = self::KEY_LESS($key);
					break;
				default:
					$where[] = self::KEY_SET($key);
					break;
			endswitch;
		endforeach;
		return $where;
	}
	/**
	 * 配置UPDATE 可用前缀 +- 对字段增加,减少
	 * @param array|string $key
	 * @return string
	 */
	static public function UPDATE_KEY(array|string $key): string
	{

		if (is_array($key)):
			return implode(
				',',
				array_map(fn($m) => self::UPDATE_KEY($m), $key)
			) . PHP_EOL;
		endif;
		$key = trim($key);
		$char = substr($key, 0, 1);
		if (!ctype_alpha($char)):
			$key = substr($key, 1);
			return match ($char) {
				'+' => self::KEY_ADD($key),
				'-' => self::KEY_PDD($key),
				default => self::KEY_SET($key)
			};
		endif;
		return self::KEY_SET($key);
	}
	/**
	 * 更新语句标准化 更新可用前缀 +-<>对应字段增加减少,减少至为零,每次增加1到指定值
	 * @param array $data
	 * @param array $where
	 */
	static public function UPDATE_VALUE(array $data, array $where = array()): array
	{
		$query = array();
		$param = array();
		foreach ($data as $key => $value):
			$key = trim($key);
			$char = substr($key, 0, 1);
			if (!ctype_alpha($char)):
				$key = substr($key, 1);
				if (is_numeric($value)):
					$value = floatval($value);
					switch ($char):
						case '+':
							$query[] = self::KEY_ADD($key);
							$param[] = $value;
							break;
						case '-':
							$query[] = self::KEY_PDD($key);
							$param[] = $value;
							break;
						case '<':
							$query[] = self::KEY_IF_PDD($key);
							$param[] = $value;
							$param[] = $value;
							break;
						case '>':
							$query[] = self::KEY_IF_ADD($key);
							$param[] = $value;
							$param[] = $value;
							break;
						case '@':
							$query[] = self::KEY_FILE($key);
							$param[] = $value;
							break;
						default:
							$query[] = self::KEY_SET($key);
							$param[] = $value;
							break;
					endswitch;
					continue;
				endif;
			endif;
			if (is_array($value)):
				$value = serialize($value);
			endif;
			$query[] = self::KEY_SET($key);
			$param[] = $value;
		endforeach;
		$query = implode(',', $query);
		if (!empty($where)):
			list($newquery, $newparam) = self::WHERE_AND($where);
			if (!empty($newquery)):
				$query .= $newquery;
				array_push($param, ...$newparam);
			endif;
		endif;
		return array($query, $param);
	}
	/**
	 *  (`key`) VALUES (...?)[,(...?)]
	 */
	static function INSERT_VALUES(array $field, int $len = 1): string
	{
		$value = str_repeat(',(' . self::fill($field) . ')', $len);
		return '(' . self::quote($field, true) . ') VALUES ' . substr($value, 1) . PHP_EOL;
	}
	static function INSERT_DATA(array $field, int $len = 1): string
	{
		$str1 = [];
		$str2 = [];
		foreach ($field as $key):
			$char = substr($key, 0, 1);
			if (!ctype_alpha($char)):
				$key = substr($key, 1);
			endif;
			switch ($char):
				case '@':
					$str2[] = 'LOAD_FILE(?)';
					break;
				default:
					$str2[] = '?';
					break;
			endswitch;
			$str1[] = $key;
		endforeach;
		return '(' . implode(',', $str1) . ') VALUES (' . $str2 . ')' . PHP_EOL;
	}
	static public function ORDER(array|string $order): string
	{
		return PHP_EOL . 'ORDER BY ' . self::KEY_ORDER($order);
	}
	static public function LIMIT(int $page, int $limit = 0): string
	{
		if (empty($limit)):
			return PHP_EOL . 'LIMIT ' . $page . ';';
		endif;
		if (empty($page) || $page < 1):
			$page = 1;
		endif;
		$start = ($page - 1) * $limit;
		return PHP_EOL . 'LIMIT ' . $start . ',' . $limit . ';';
	}

	public static function tablename(string $table): string
	{
		return self::app()->tablepre . $table;
	}
	public static function tableqoute(string $table): string
	{
		return self::quote(self::tablename($table));
	}
	public static function PROFILES(): array
	{
		$mydb = self::app();
		if (isset($mydb->wlink)):
			$querylist = $mydb->wlink->querySQL('SHOW PROFILES;', 2);
			if (isset($mydb->rlink) && $mydb->rlink != $mydb->wlink):
				$querylist += $mydb->rlink->querySQL('SHOW PROFILES;', 2);
			endif;
			return $querylist;
		endif;
		return array();
	}
	public static function LENGTH(): int
	{

		$mydb = self::app();
		if (isset($mydb->wlink)):
			$length = $mydb->wlink->length;
			if (isset($mydb->rlink) && $mydb->rlink != $mydb->wlink):
				$length += $mydb->rlink->length;
			endif;
			return $length;
		endif;
		return 0;
	}
}
