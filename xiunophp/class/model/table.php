<?php
namespace model;

use Exception;
use MyDB;
/**
 * 数据表操作类
 */
class table
{
	public array $conf;
	public string $key;
	public function __construct(string $table, ?string $key = null, ?string $dbname = null)
	{
		$dbname = $dbname ?: MyDB::rconf('name');
		$this->conf = array(
			'table' => $table,
			'quotetable' => '`' . $table . '`',
			'fulltable' => '`' . $dbname . '`.`' . $table . '`',
			'dbname' => $dbname,
		);
		if ($key) $this->key = $key;
	}
	// hook model_table_methods.php
	public function get_primary(string $primary = '')
	{
		
		if(empty($primary)):
			if(empty($this->key)):
				throw new Exception(self::class.':未能找到主键');
			endif;
			return $this->key;
		endif;
		return $primary;
	}
	/**
	 * 给字段生成带表名反引号
	 */
	public function quoteKey(string|array $indexkey = ''): string
	{
		if (!empty($indexkey)) return $this->conf['quotetable'] . '.' . MyDB::quote($indexkey);
		return $this->conf['quotetable'] . '.' . MyDB::quote($this->key);
	}
	public function quoteColumn(string|array $indexkey = '')
	{
		if (!empty($indexkey)) return MyDB::quote($indexkey);
		return MyDB::quote($this->key);
	}
	public function quoteFullKey(string $str): string
	{
		return $this->conf['fulltable'] . '.' . MyDB::quote($str);
	}
	public function sql_select(string|array $column): string
	{
		return MyDB::sql_select($this->conf['fulltable'], $column);
	}
	public function sql_insert(): string
	{
		return MyDB::sql_insert($this->conf['fulltable']);
	}
	public function sql_delete(): string
	{
		return MyDB::sql_delete($this->conf['fulltable']);
	}
	public function sql_update(): string
	{
		return MyDB::sql_update($this->conf['fulltable']);
	}
	/**
	 * 开启事务
	 */
	public function start(...$arg): bool
	{
		return MyDB::rdb()->commitStart(...$arg);
	}
	/**
	 * 回滚事务
	 */
	public function back(...$arg): bool
	{
		return MyDB::rdb()->commitBack(...$arg);
	}
	/**
	 * 结束事务
	 */
	public function end(...$arg): bool
	{
		return MyDB::rdb()->commitEnd(...$arg);
	}
	/**
	 * 预处理查询(从链接)
	 */
	public function execute(string $query, array $param = array(), int|string $mode = 1): mixed
	{
		return MyDB::rdb()->executeSQL($query, $param, $mode);
	}
	/**
	 * 预处理查询(主链接)
	 * 
	 */
	public function execute_wlink(string $query, array $param = array(), int|string $mode = 1): mixed
	{
		return MyDB::wdb()->executeSQL($query, $param, $mode);
	}
	/**
	 * 事务批量插入
	 */
	public function executeCommit(string $query, array $param = array())
	{
		return MyDB::rdb()->executeCommit($query, $param);
	}
	/**
	 * 批量插入
	 */
	public function executeMap(string $query, array $param = array()): void
	{
		MyDB::rdb()->executeMap($query, $param);
	}
	/**
	 * 查询
	 * 0 行数 
	 * 1多行索引 
	 * 2多行无序 
	 * 3多行混合 
	 * 4单行索引 
	 * 5单行无 
	 * 6单行类 
	 * 7单行首列
	 */
	public function query(string $query, int|string $mode = 1): mixed
	{
		return MyDB::rdb()->querySQL($query, $mode);
	}
	/**
	 * 查询返回影响行数或者insert_id
	 */
	public function exec(string $query, int $mode = 0): int|string
	{
		return MyDB::rdb()->execSQL($query, $mode) ?: 0;
	}
	/**
	 * 查询返回影响行数或者insert_id
	 */
	public function exec_wlink(string $query, int $mode = 0): int|string
	{
		return MyDB::wdb()->execSQL($query, $mode) ?: 0;
	}
	/**
	 * 更改字段
	 */
	public function alert($sql,$mode=0)
	{
		return $this->exec_wlink(MyDB::SQL_ALERT($this->conf['fulltable']).$sql,$mode);
	}
	/**
	 * 多行查询
	 */
	public function multi(string $query, int|string $mode = 0): mixed
	{
		$result = MyDB::rdb()->multiSQL($query, $mode);
		return $mode === 0 ? array_sum($result) : $result;
	}
	/**
	 * 查询并返回数据
	 */
	public function select(string $where = '', array $param = array(), int|string $mode = 1, string|array $column = ''): mixed
	{
		return $this->execute(
			$this->sql_select($column) . $where,
			$param,
			$mode
		);
	}
	/**
	 * 查询并返回所有数据
	 */
	public function selectAll(string $where = '', array $param = array(), string|array $column = ''): array
	{
		return $this->execute(
			$this->sql_select($column) . $where,
			$param,
			1
		) ?: array();
	}
	/**
	 * 查询并返回第一条数据
	 */
	public function selectFirst(string $where = '', array $param = array(), string|array $column = ''): array
	{
		return $this->select($where, $param, 4, $column) ?: array();
	}
	/**
	 * 查询并第一列数据
	 */
	public function selectValue(string $where = '', array $param = array(), string|array $column = ''): mixed
	{
		return $this->select($where, $param, 7, $column) ?: false;
	}
	/**
	 * 返回带indexkey索引数据
	 */
	public function selectList(string $where = '', array $param = array(), string|array $column = ''): array
	{
		$result = $this->select($where, $param, 1, $column) ?: array();
		if (empty($this->conf['key'])) return $result;
		return array_column($result, null, $this->key);
	}
	/**
	 * 统计总数
	 */
	public function selectCount(string $where = '', array $param = array()): int
	{
		return $this->selectValue($where, $param, 'count(*)') ?? 0;
	}
	/**
	 * 最大id
	 */
	public function selectMax(string $where = '', array $param = array(), ?string $column = null): int|string
	{
		return $this->selectValue($where, $param, 'max(' . $this->quoteColumn($column) . ')') ?: 0;
	}
	/**
	 * 参考 select
	 * @param array $where
	 * @param string $endsql
	 * @param integer $mode
	 * @param string|arrray $column
	 */
	public function where(array $where, string $endsql = '', int $mode = 1, string|array $column = ''): mixed
	{
		return $this->select(...MyDB::WHERE_AND($where, $endsql, $mode, $column));
	}
	/**
	 * 参考 selectAll
	 * @param array $where
	 * @param string $endsql
	 * @param string $column
	 */
	public function whereAll(array $where, string $endsql = '', string|array $column = ''): array
	{
		return $this->selectAll(...MyDB::WHERE_AND($where, $endsql, $column));
	}
	/**
	 * 参考 selectFirst
	 * @param array $where 条件
	 * @param string $endsql 附加SQL 如order limit
	 * @param array|string  $column 特定显示列
	 * @param array $where 条件
	 */
	public function whereFirst(array $where, string $endsql = '', string|array $column = ''): array
	{
		return $this->selectFirst(...MyDB::WHERE_AND($where, $endsql, $column));
	}
	/**
	 * 参考 selectValue
	 * @param array $where 条件
	 * @param array|string  $column 特定显示列
	 */
	public function whereValue(array $where, string|array $column = ''): mixed
	{
		return $this->selectValue(...MyDB::WHERE_AND($where, '', $column));
	}
	/**
	 * 参考 selectMax
	 * @param array $where 条件
	 * @param array|string  $column 特定显示列
	 */
	public function whereMax(array $where, $column = ''): mixed
	{
		return $this->selectValue(...MyDB::WHERE_AND($where, '', 'max(' . $this->quoteColumn($column) . ')')) ?: 0;
	}
	/**
	 * 参考 selectValue
	 * @param array $where 条件
	 */
	public function whereCount(array $where): int
	{
		return $this->selectCount(...MyDB::WHERE_AND($where));
	}
	public function whereOrder(array $where = array(), string|array $order = '', int|array $limit = 0, string|array $column = '')
	{
		list($sql, $param) = MyDB::WHERE_AND($where);
		if (!empty($order)):
			$sql .= MyDB::ORDER($order);
		endif;
		$mode = 1;
		if (!empty($limit)):
			if (is_int($limit)):
				if ($limit === 1):
					$mode = 4;
				endif;
				$sql .= MyDB::LIMIT($limit);
			elseif (is_array($limit)):
				$sql .= MyDB::LIMIT(...$limit);
			endif;
		endif;
		return $this->select($sql, $param, $mode, $column);
	}
	/**
	 * 更新数据
	 */
	public function update(string $updateStr = '', array $param = array()): int
	{
		// hook model_table_update.php
		return $this->execute_wlink($this->sql_update() . $updateStr, $param, 0) ?: 0;
	}
	/**
	 * 如果不是标准(table,id)打开 不可用
	 * 用数组更新数据  
	 * 更新字段值为数字可用前缀 +-<>对应字段增加减少,减少至为零,每次增加1到指定值  
	 * 如果更新数据项值为数组会被 serialize();  
	 */
	public function update_by_value(array $json, mixed $value): int
	{
		// hook model_table_update_by_value.php
		return $this->update_by_where($json, array($this->key => $value));
	}
	/**
	 * 附加指定条件更新数据  
	 * 更新字段值为数字可用前缀 +-<>对应字段增加减少,减少至为零,每次增加1到指定值  
	 * 如果更新数据项值为数组会被 serialize();  
	 * 
	 */
	public function update_by_where(array $json, array $where = array()): int
	{
		if (empty($json)) return 0;
		// hook model_table_update_by_where.php
		list($sql, $param) = MyDB::UPDATE_VALUE($json, $where);
		return $this->update($sql, $param);
	}
	public function update_execute(array $json, $endsql = '', $enddata = array()): int
	{
		// hook model_table_update_execute.php
		$sql = MyDB::UPDATE_KEY(array_keys($json));
		$param = array_values($json);
		if (!empty($endsql)):
			$sql .= $endsql;
			array_push($param, ...$enddata);
		endif;
		return $this->update($sql, $param);
	}
	/**
	 * 批量更新
	 * $where不要附加 WHERE
	 */
	public function update_multi(array $keys, string|array $where, array $mapjson): int|false
	{
		// hook model_table_update_multi.php
		$sql = $this->sql_update() . MyDB::UPDATE_KEY($keys);
		if (!empty($where)):
			if (is_string($where)):
				$sql .= ' WHERE ' . $where;
			else:
				$sql .= ' WHERE ' . implode(' AND ', MyDB::WHERE_KEY($where));
			endif;
		endif;
		return $this->executeCommit(
			$sql,
			$mapjson
		);
	}
	/**
	 * 清空数据
	 */
	public function truncate():int
	{
		return $this->exec('TRUNCATE '.$this->conf['fulltable'],0);
	}
	/**
	 * 插头数据
	 */
	public function insert(string $sql = '', array $param = array(), int $mode = MyDB::MODE_INSERT_ID): int
	{
		// hook model_table_insert.php
		return $this->execute_wlink($this->sql_insert() . $sql, $param, $mode);
	}
	/**
	 * 插入数组数据
	 */
	public function insert_json(array $json,$mode=MyDB::MODE_INSERT_ID): int
	{
		// hook model_table_insert_json.php
		if (empty($json)) return 0;
		return $this->insert(
			MyDB::INSERT_VALUES(array_keys($json), 1),
			array_values($json),
			$mode
		);
	}
	/**
	 * 更新/插入一个带字段索引一维数据
	 * @param array $json
	 * @return integer
	 */
	public function insert_update(array $json): int
	{
		// hook model_table_insert_update.php
		$keys = array_keys($json);
		$sql = MyDB::INSERT_VALUES($keys,1);
		$sql .= ' AS `_NEW` ON DUPLICATE KEY UPDATE '.implode(',',array_map(fn($m)=>MyDB::quote($m) . ' = '.MyDB::quote('_NEW.'.$m),$keys));
		return $this->insert($sql,array_values($json),0);
	}
	/**
	 * 批量更新/插入一个二维数据
	 * @param array<array> $mapjson 带索引二维数据
	 * @param string $primary 主键
	 */
	public function insert_map_update($mapjson): int
	{
		$keys = array_keys($mapjson[array_key_first($mapjson)]);
		$sql = MyDB::INSERT_VALUES($keys,count($mapjson));
		$sql .= ' AS `_NEW` ON DUPLICATE KEY UPDATE '.implode(',',array_map(fn($m)=>MyDB::quote($m) . ' = '.MyDB::quote('_NEW.'.$m),$keys));
		$param = [];
		foreach($mapjson as $v):
			array_push($param,...array_values($v));
		endforeach;
		return $this->insert($sql, $param,0);
	}
	/**
	 * 根据固定条目 逐条插入数据,遇到错误终止插入 MYISAM不支持事务
	 */
	public function insert_commit_map(array $map, array $data): array|int
	{
		// hook model_insert_commit_map.php
		return $this->executeCommit(
			$this->sql_insert() . MyDB::INSERT_VALUES($map, 1),
			$data
		) ?: 0;
	}
	/**
	 * 不使用事务直接插入
	 * INSERT INTO [FROM] (`aa`)VALUES(?),(?)....
	 */
	public function insert_full_map(array $map, array $data)
	{
		// hook model_insert_full_map.php
		$sql  = $this->sql_insert() . MyDB::INSERT_VALUES($map, count($data));
		$param = array_merge_recursive(...$data);
		return $this->execute($sql, $param, 0) ?: 0;
	}
	public function replace($sql, $param): int
	{
		return $this->execute_wlink(MyDB::sql_replace($this->conf['fulltable']) . $sql, $param, 0);
	}
	public function replace_json($json): int
	{
		return $this->replace(
			MyDB::INSERT_VALUES(array_keys($json), 1),
			array_values($json),
		);
	}
	/**
	 * 删除数据
	 */
	public function delete(string $where = '', array $param = array()): int
	{
		$sql = $this->sql_delete() . $where;
		return $this->execute_wlink($sql, $param, 0);
	}
	/**
	 * 删除数据
	 */
	public function delete_by_where(array $where): int
	{
		return $this->delete(...MyDB::WHERE_AND($where));
	}
	/**
	 * 查询主键范围数据
	 */
	public function fetch($value): array
	{
		return $this->whereFirst(array($this->key => $value)) ?: array();
	}
	/**
	 * 查询主键范围数据
	 */
	public function fetchAll(array $values): array
	{
		return $this->whereAll(array($this->key => $values)) ?: array();
	}
	public function MaxID()
	{
		return $this->whereMax(array(), $this->key);
	}
	/**
	 * 表结构
	 */
	public function columns(): array
	{
		if ($this->conf['dbname'] == 'information_schema'):
			throw new \Error(static::class . '::getField()', 99999);
		endif;
		return array_column(MyDB::t('information_schema.COLUMNS')->where(
			array(
				'TABLE_SCHEMA' => $this->conf['dbname'],
				'TABLE_NAME' => $this->conf['table']
			),
			MyDB::ORDER(['ORDINAL_POSITION' => 'asc']),
			2,
			array('COLUMN_NAME')
		), 0);
	}
	/**
	 * 表结构引擎
	 */
	public function column_key(string $name): array
	{
		if ($this->conf['dbname'] == 'information_schema'):
			throw new \Error(static::class . '::getField()', 99999);
		endif;
		return MyDB::t('information_schema.COLUMNS')->where(
			array(
				'TABLE_SCHEMA' => $this->conf['dbname'],
				'TABLE_NAME' => $this->conf['table'],
				'COLUMN_NAME'=>$name
			),
			MyDB::ORDER(['ORDINAL_POSITION' => 'asc']),
			4,
		);
	}
	public function show_index_type(string $name):string
	{
		$result = $this->execute('SHOW INDEX FROM '.$this->conf['fulltable'] .' WHERE `Key_name` = ? ',[$name],4)?:array();
		return $result['Index_type']??'';
	}
}
