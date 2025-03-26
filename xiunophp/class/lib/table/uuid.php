<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 数据库表 扩展函数 给有特殊癖好的人生成指定长度的UID用户
 */
namespace lib\table;
use MyDB;
/**
 * 生成指定长度的数字uuid
 * @param int $size 长度
 * @param string $key 索引字段
 * @method $this->random_uuid($size,$key)
 */
trait uuid{
	public array $functionlist;
	/**
	 * 记录最后更新随机ID最低位数
	 */
	public int $uuid_length = 0;
	/**
	 * 生成固定长度的唯一随机ID
	 * @example location MyDB::table('user','uid')->random_uuid(5,'uid') 产生5位未使用随机uid
	 * @param string $key 字段名
	 * @param int $size 位数
	 */
	public function random_uuid(int $size=5,string $key=''):int
	{
		$key = $key?:$this->key;
		if($size<$this->uuid_length):
			$size = $this->uuid_length;
		endif;
		$name = $this->random_uuid_name($key,$size);
		if(!isset($this->functionlist)):
			$this->functionlist = MyDB::functions();
		endif;
		if(!in_array($name,$this->functionlist)):
			$this->random_uuid_create($name,$key,$size);
		endif;
		$result = $this->query('SELECT `'.$name.'`();',7);
		if(!$result):
			return $this->random_uuid($size+1,$key);
		endif;
		return $result;
	}
	/**
	 * 生成函数名
	 */
	public function random_uuid_name(string $key,int $size):string
	{
		return 'uuid_'.$this->table.'_'.$key.'_'.$size;
	}
	/**
	 * 创建函数过程
	 */
	public function random_uuid_create(string $name,string $key,int $size=5):void
	{
		$this->exec('DROP FUNCTION IF EXISTS '. $name);
		$keyname = $this->quoteKey($key);
		$countsql = $this->sql_select('COUNT(*)');
		$query  = 'CREATE FUNCTION `'.$name.'`() ';
		$query .= 'RETURNS BIGINT UNSIGNED NOT DETERMINISTIC CONTAINS SQL SQL SECURITY DEFINER BEGIN DECLARE `uuid_min` BIGINT DEFAULT 1;';
		$query .= 'DECLARE `uuid_id` BIGINT DEFAULT 1;';
		$query .= 'DECLARE `uuid_max` BIGINT DEFAULT 1;';
		$query .= 'SET `uuid_min` = POW(10,'.($size-1).');';
		$query .= 'SET `uuid_max` = (';
		$query .= $countsql.' WHERE '.$keyname.'>=`uuid_min` AND '.$keyname.'<`uuid_min`*10';
		$query .= ');';
		$query .= 'IF (`uuid_max` >= `uuid_min`*9) THEN RETURN 0 ; END IF;';
		$query .= ' label:WHILE TRUE DO SET `uuid_id` = TRUNCATE(RAND(),'.$size.')*`uuid_min`*10;IF (';
		$query .= 'SELECT `uuid_id` >= `uuid_min`';
		$query .= ') AND (';
		$query .= $countsql.' WHERE '.$keyname.' = `uuid_id`';
		$query .= ') <1 THEN LEAVE label;';
		$query .= ' END IF; END WHILE label;';
		$query .= ' RETURN `uuid_id`; END';
		$this->exec($query);
		$this->functionlist[] = $name;
	}
}