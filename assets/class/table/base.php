<?php
namespace table;
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

?>