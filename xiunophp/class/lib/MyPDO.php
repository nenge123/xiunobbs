<?php

/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 数据库驱动
 */
namespace lib;
use MyApp;
class MyPDO extends \PDO
{
	public int $length;
	public int $isdebug;
	public function __construct(array $conf,?string $scheme = null)
	{
		$this->isdebug = intval(defined('DEBUG')&&DEBUG)?:0;
		$settings = array(
			'scheme' => $scheme ?? 'mysql',
			'id'   => 1,
			'host' => ini_get("mysqli.default_host"),
			'port' => ini_get("mysqli.default_port"),
			'charset' => 'utf8mb4',
			'name' => '',
			'user' => ini_get("mysqli.default_user"),
			'password' => ini_get("mysqli.default_pw"),
			'socket' => ini_get("mysqli.default_socket")
		);
		foreach ($conf as $k => $v) :
			if (!empty($v)) :
				$settings[$k] = $v;
			endif;
		endforeach;
		$dsn  = $settings['scheme'] . ':';
		$dsn .= 'host=' . $settings['host'];
		$dsn .= ';charset=' . $settings['charset'];
		if (!empty($settings['name'])) :
			$dsn .= ';dbname=' . $settings['name'];
		endif;
		$options = array(
			self::ATTR_ERRMODE => defined('MYSQL_DEBUG')?self::ERRMODE_EXCEPTION:self::ERR_NONE,
		);
		if (defined('\PDO::MYSQL_ATTR_COMPRESS')):
			$options[self::MYSQL_ATTR_COMPRESS] = true;
			if ($this->isdebug>0) :
				$options[self::MYSQL_ATTR_INIT_COMMAND] = 'SET profiling = 1,profiling_history_size=100;';
			endif;
			if (!empty($conf['ssl_key'])):
				$options[self::MYSQL_ATTR_SSL_KEY] = $conf['ssl_key'];
				$options[self::MYSQL_ATTR_SSL_CERT] = $conf['ssl_cert'];
				$options[self::MYSQL_ATTR_SSL_CA] = $conf['ssl_ca'];
			endif;
		endif;
		parent::__construct(
			$dsn,
			$settings['user'],
			$settings['pw'],
			$options
		);
		$this->length = 0;
	}
	public function setReport(bool $bool=false):void
	{
		$this->setAttribute(self::ATTR_ERRMODE,$bool?self::ERRMODE_EXCEPTION:self::ERR_NONE);
	}
	public function serverVersion()
	{
		return $this->getAttribute(self::ATTR_SERVER_VERSION);
	}
	public function clientVersion()
	{
		return $this->getAttribute(self::ATTR_CLIENT_VERSION);
	}
	public function execSQL(string $query, $mode = 0): int
	{
		$row = $this->exec($query);
		if (!empty($this->errorMessage())):
			$this->setError('a sql query error');
		endif;
		$this->length++;
		return $mode == -1 ? $this->lastInsertId() : ($row ?: 0);
	}
	/**
	 * 0 行数 
	 * 1多行索引 
	 * 2多行无序 
	 * 3多行混合 
	 * 4单行索引 
	 * 5单行无 
	 * 6单行类 
	 * 7单行首列
	 */
	public function querySQL(string $query, int|string $mode = 1): mixed
	{
		$PDOStatement = $this->query($query);
		if (!empty($this->errorMessage())):
			$this->setError('a sql query error');
			return $this->fetch_result_by_null($mode);
		endif;
		$this->length++;
		return $this->query_result_mode($PDOStatement, $mode);
	}
	public function multiSQL(string $query, int|string $mode = 0): array
	{
		$PDOStatement = $this->query($query);
		if (!empty($this->errorMessage())):
			$this->setError('multi sql query error');
			return $this->fetch_result_by_null($mode);
		endif;
		$data = array();
		if ($PDOStatement instanceof \PDOStatement) :
			while ($PDOStatement) :
				if ($mode == 0) :
					$data += $PDOStatement->rowCount() ?: 0;
				else :
					$data[] = $this->query_result_mode($PDOStatement, $mode) ?: $this->lastInsertId();
				endif;
				$this->length++;
				if (!$PDOStatement->nextRowset()) break;
			endwhile;
			if ($PDOStatement instanceof \PDOStatement) :
				$PDOStatement->closeCursor();
			endif;
		endif;
		return $data;
	}
	public function executeSQL(string $query, array $params = array(), int|string $mode = 1): mixed
	{
		$PDOStatement = $this->prepare($query);
		$PDOStatement->execute($params);
		if (empty($this->errorMessage($PDOStatement))):
			$this->length++;
			return $this->query_result_mode($PDOStatement, $mode);
		else:
			$this->setError($query, $PDOStatement);
			return $this->fetch_result_by_null($mode);
		endif;
	}
	public function prepareSQL(string $query, array $params = array(), int|string $mode = 1): mixed
	{
		return $this->executeSQL($query, $params, $mode);
	}
	public function setError($query, ?\PDOStatement $PDOStatement=null)
	{
		$info = empty($PDOStatement)?$this->errorInfo():$PDOStatement->errorInfo();
		if (!empty($info[2])):
			if ($this->isdebug>0):
				MyApp::xn_log('SQL:'.$query.'	error:'.$this->errorCode().'	errstr:'.$this->errorMessage(), 'db_error');
				//throw new \Exception($query. '<br>['.$info[0].']'.$info[2],intval($info[1]),);
			endif;
		endif;
	}
	public function executeCommit(string $query, array $params): int
	{
		if(empty($params)):
			return 0;
		endif;
		$length = 0;
		$this->commitStart();
		$PDOStatement = $this->prepare($query);
		foreach ($params as $key => $param) :
			if (empty($param) || !is_array($param)):
				continue;
			endif;
			$param = array_values($param);
			$PDOStatement->execute($param);
			if (!empty($this->errorMessage($PDOStatement))):
				$this->commitBack();
				$this->setError($query, $PDOStatement);
			endif;
			$PDOStatement->closeCursor();
			$length++;
			$this->length++;
		endforeach;
		print_r($PDOStatement->errorCode());
		return $length;
	}
	public function executeMap(string $query, array $paramlist): void
	{
		$PDOStatement = $this->prepare($query);
		foreach ($paramlist as $param) :
			$param = array_values($param);
			$PDOStatement->execute($param);
			if (!empty($this->errorMessage($PDOStatement))):
				$this->setError($query, $PDOStatement);
			endif;
			$PDOStatement->closeCursor();
			$this->length++;
		endforeach;
	}
	public function query_result_mode(\PDOStatement|bool $PDOStatement, int|string $mode): mixed
	{
		if ($PDOStatement instanceof \PDOStatement) :
			return $this->fetch_result_by_mode($PDOStatement, $mode);
		else :
			return $this->fetch_result_by_null($mode);
		endif;
	}
	public function fetch_result_by_null($mode): mixed
	{
		switch (true):
			case $mode === -1:
				return $this->lastInsertId();
				break;
			case $mode === 0:
				return 0;
				break;
			case in_array($mode, array(6, 7)):
				return NULL;
				break;
				break;
			default:
				return array();
				break;
		endswitch;
	}
	public function fetch_result_by_mode(\PDOStatement $PDOStatement, int|string $mode = 1)
	{
		switch (true) {
			case is_string($mode):
			case $mode === -1:
				return $this->lastInsertId();
				break;
			case $mode === 0:
				return $PDOStatement->rowCount();
				break;
				#多行带键索引
			case $mode === 1:
				return $PDOStatement->fetchAll(self::FETCH_ASSOC) ?: array();
				break;
				#多行数字序列
			case $mode === 2:
				return $PDOStatement->fetchAll(self::FETCH_NUM) ?: array();
				break;
				#同时包含多行的[数字和键值索引]
			case $mode === 3:
				return $PDOStatement->fetchAll(self::FETCH_BOTH) ?: array();
				break;
				#单行键值索引
			case $mode === 4:
				return $PDOStatement->fetch(self::FETCH_ASSOC, self::FETCH_ORI_FIRST) ?: array();
				break;
				#单行数字索引
			case $mode === 5:
				return $PDOStatement->fetch(self::FETCH_NUM, self::FETCH_ORI_FIRST) ?: array();
				break;
				#单行对象引用
			case $mode === 6:
				return $PDOStatement->fetch(self::FETCH_OBJ, self::FETCH_ORI_FIRST);
				break;
				#单行某列的值
			case $mode === 7:
				#单行指定列值
				return $PDOStatement->fetchColumn(0);
				break;
			case $mode === 10:
				return $PDOStatement->getIterator();
			default:
				return $this->fetch_result_by_mode($PDOStatement, 1);
				break;
		}
	}
	public function errorMessage(?\PDOStatement $PDOStatement=null):?string
	{
		if($PDOStatement) return $this->errorInfo()[2];
		return $this->errorInfo()[2];
	}
	public function commitStart(): bool
	{
		return $this->beginTransaction();
	}
	public function commitBack(): bool
	{
		return $this->rollBack();
	}
	public function commitEnd(): bool
	{
		return $this->commit();
	}
	public function insert_id()
	{
		$id = $this->lastInsertId();
		return $id >= 0 ? $id : $this->querySQL('SELECT LAST_INSERT_ID();', 7);
	}
}
