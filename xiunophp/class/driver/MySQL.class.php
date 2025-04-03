<?php

class MySQL extends \mysqli
{
	public int $length;
	public function __construct(array $conf)
	{
		parent::__construct();
		$settings = array(
			'id'   => 1,
			'host' => ini_get("mysqli.default_host"),
			'port' => ini_get("mysqli.default_port"),
			'charset' => 'utf8mb4',
			'name' => '',
			'user' => ini_get("mysqli.default_user"),
			'password' => ini_get("mysqli.default_pw"),
			'socket' => ini_get("mysqli.default_socket")
		);
		$this->ssl_set(
			$conf['ssl_key'] ?? null,
			$conf['ssl_cert'] ?? null,
			$conf['ssl_ca'] ?? null,
			$conf['ssl_capath'] ?? null,
			$conf['ssl_cipher'] ?? null,
		);
		foreach ($conf as $k => $v) :
			if (!empty($v)) :
				$settings[$k] = $v;
			endif;
		endforeach;
		$this->setReport(defined('MYSQL_DEBUG'));
		#\mysqli_report(defined('\DEBUG')?MYSQLI_REPORT_ALL:MYSQLI_REPORT_OFF);
		$this->real_connect(
			$settings['host'],
			$settings['user'],
			$settings['password'],
			$settings['name'],
			$settings['port'],
			$settings['socket'],
			MYSQLI_CLIENT_COMPRESS
		);

		if (empty($this->connect_errno)) :
			self::set_charset($settings['charset']);
			$this->length = 0;
			if (defined('DEBUG')) :
				$this->query('SET profiling = 1,profiling_history_size=100;');
				$this->length = 1;
			endif;
		endif;
	}
	public function setReport(bool $bool = false): void
	{
		if ($bool):
			\mysqli_report(MYSQLI_REPORT_STRICT);
		else:
			\mysqli_report(MYSQLI_REPORT_OFF);
		endif;
	}
	public function serverVersion()
	{
		return $this->server_info;
	}
	public function clientVersion()
	{
		return $this->client_info;
	}
	public function execSQL(string $query, $mode = 0): int|string
	{
		$mysqli_result = $this->query($query);
		if (!empty($this->error)):
			$this->setError('a sql query error');
		endif;
		if(defined('DEBUG')&&DEBUG>0):
			MyApp::xn_log($query, 'db_exec');
		endif;
		$this->length++;
		if ($mysqli_result instanceof \mysqli_result) :
			$mysqli_result->free();
		endif;
		return $mode == -1 ? $this->insert_id : ($this->affected_rows ?: 0);
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
		$mysqli_result = $this->query($query);
		if (!empty($this->error)):
			$this->setError('a sql query error');
		endif;
		if(defined('DEBUG')&&DEBUG>0):
			MyApp::xn_log($query, 'db_exec');
		endif;
		$this->length++;
		return $this->query_result_mode($mysqli_result, $mode);
	}
	public function multiSQL(string $query, int $mode = 0): array
	{
		$this->multi_query($query);
		if (!empty($this->error)):
			$this->setError('multi sql query error');
		endif;
		if(defined('DEBUG')&&DEBUG>0):
			MyApp::xn_log($query, 'db_exec');
		endif;
		$data = array();
		if ($this->errno == 0) :
			while ($this->more_results()) :
				$this->next_result();
				$this->length++;
				$mysqli_result = $this->store_result();
				$data[] = $this->query_result_mode($mysqli_result, $mode);
			endwhile;
		endif;
		return $data;
	}
	public function executeSQL(string $query, array $params = array(), int|string $mode = 1): mixed
	{
		if (method_exists($this, 'execute_query')) :
			$mysqli_result = $this->execute_query($query, $params);
			if (!empty($this->error)):
				$this->setError($query);
			endif;
			if(defined('DEBUG')&&DEBUG>0):
				MyApp::xn_log($query, 'db_exec');
			endif;
			$this->length++;
			return $this->query_result_mode($mysqli_result, $mode);
		endif;
		return $this->prepareSQL($query, $params, $mode);
	}
	public function prepareSQL(string $query, array $params = array(), int|string $mode = 1): mixed
	{
		$mysqli_stmt = $this->stmt_init();
		$mysqli_stmt->prepare($query);
		if (!empty($mysqli_stmt->error)):
			$this->setError($query, $mysqli_stmt);
			return $this->fetch_result_by_null($mode);
		endif;
		$mysqli_stmt->execute($params);
		if (!empty($mysqli_stmt->error)):
			$this->setError($query, $mysqli_stmt);
			return $this->fetch_result_by_null($mode);
		endif;
		$mysqli_result = $mysqli_stmt->get_result();
		if(defined('DEBUG')&&DEBUG>0):
			MyApp::xn_log($query, 'db_exec');
		endif;
		$this->length++;
		return $this->query_result_mode($mysqli_result, $mode);
	}
	public function setError(string $query, ?\mysqli_stmt $mysqli_stmt = null)
	{
		if (empty($mysqli_stmt)):
			$errorlist = $this->error_list[0];
		else:
			$errorlist = $mysqli_stmt->error_list[0];
		endif;
		if (!empty($errorlist['error'])):
			if (defined('DEBUG')&&DEBUG):
				MyApp::xn_log('SQL:'.$query.'	error:'.$this->errorCode().'	errstr:'.$this->errorMessage(), 'db_error');
				//throw new \Exception($query. '<br>['.$errorlist['sqlstate'].']'.$errorlist['error'],$this->errorCode(),);
			endif;
		endif;
	}
	public function executeCommit(string $query, array $params): int
	{
		if (empty($params)):
			return 0;
		endif;
		$mysqli_stmt = $this->stmt_init();
		$mysqli_stmt->prepare($query);
		if (empty($mysqli_stmt->error)):
			$this->commitStart();
			$length = 0;
			foreach ($params as $key => $param) :
				if (empty($param) || !is_array($param)) continue;
				$param = array_values($param);
				$mysqli_stmt->execute($param);
				if (!empty($mysqli_stmt->error)):
					$this->commitBack();
					if ($mysqli_stmt instanceof \mysqli_stmt && !empty($mysqli_stmt->id)):
						$mysqli_stmt->close();
					endif;
					$this->setError($query, $mysqli_stmt);
					return $this->fetch_result_by_null(0);
				endif;
				$mysqli_stmt->reset();
				$length++;
				$this->length++;
			endforeach;
			if(defined('DEBUG')&&DEBUG>0):
				MyApp::xn_log($query, 'db_exec');
			endif;
			$this->commitEnd();
			return $length;
		else:
			$this->setError($query, $mysqli_stmt);
			return $this->fetch_result_by_null(0);
		endif;
	}
	public function executeMap(string $query, array $paramlist): void
	{
		$mysqli_stmt = $this->stmt_init();
		$mysqli_stmt->prepare($query);
		if (empty($mysqli_stmt->error)):
			foreach ($paramlist as $param) :
				$param = array_values($param);
				$mysqli_stmt->execute($param);
				if (!empty($mysqli_stmt->error)):
					$this->setError($query, $mysqli_stmt);
				endif;
				$mysqli_stmt->reset();
				$this->length++;
			endforeach;
			if(defined('DEBUG')&&DEBUG>0):
				MyApp::xn_log($query, 'db_exec');
			endif;
		else:
			$this->setError($query, $mysqli_stmt);
		endif;
	}
	public function query_result_mode(\mysqli_result|bool $mysqli_result, int|string $mode): mixed
	{
		if ($mysqli_result instanceof \mysqli_result) :
			return $this->fetch_result_by_mode($mysqli_result, $mode);
		else :
			return $this->fetch_result_by_null($mode);
		endif;
	}
	public function fetch_result_by_null($mode): mixed
	{
		switch (true):
			case $mode === -1:
				return $this->insert_id;
				break;
			case $mode === 0:
				return $this->affected_rows;
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
	public function fetch_result_by_mode(\mysqli_result $mysqli_result, int|string $mode = 1): mixed
	{
		switch (true) {
			case $mode === -1:
				#插入ID
				return $this->insert_id;
				break;
			case $mode === 0:
				#影响行数
				return $mysqli_result->num_rows;
				break;
			#多行带键索引
			case $mode === 1:
				return $mysqli_result->fetch_all(MYSQLI_ASSOC);
				break;
			#多行数字序列
			case $mode === 2:
				return $mysqli_result->fetch_all(MYSQLI_NUM);
				break;
			#同时包含多行的[数字和键值索引]
			case $mode === 3:
				return $mysqli_result->fetch_all(MYSQLI_BOTH);
				break;
			#单行键值索引
			case $mode === 4:
				return $mysqli_result->fetch_assoc();
				break;
			#单行数字索引
			case $mode === 5:
				return $mysqli_result->fetch_row();
				break;
			#单行对象引用
			case $mode === 6:
				return $mysqli_result->fetch_object();
				break;
			#单行某列的值
			case $mode === 7:
				#单行指定列值
				return $mysqli_result->fetch_column(0);
				break;
			case $mode === 10:
				return $mysqli_result->getIterator();
			case is_string($mode):
				return array_column($this->fetch_result_by_mode($mysqli_result, 1), null, $mode);
				break;
			default:
				return $this->fetch_result_by_mode($mysqli_result, 1);
				break;
		}
	}
	public function commitStart(...$arg): bool
	{
		return $this->begin_transaction(...$arg);
	}
	public function commitBack(...$arg): bool
	{
		return $this->rollback(...$arg);
	}
	public function commitEnd(...$arg): bool
	{
		return $this->commit(...$arg);
	}
	public function errorMessage(): string
	{
		return $this->error;
	}
	public function errorCode(): int
	{
		return $this->errno;
	}
}
