<?php
class MyApp implements \ArrayAccess
{
	public static $_app;
	public array $conf = array();
	public array $datas = array();
	public function __construct($conf = array())
	{
		self::$_app = $this;
		$this->datas = array(
			'extension' => 'html',
			'https' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on',
			'rewriteopen'=>str_ends_with($_SERVER['PHP_SELF'], 'index.php'),
			'rewriteroot'=>dirname($_SERVER['PHP_SELF']).'/',
			'rewriteMode' => false,
			'gzip' => str_contains($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip'),
			'session'=>array(),
			'g_session_invalid' => FALSE,
		);
		$this->routerExport();
		define('APP_SITE', $this->convert_site(APP_PATH));
		$this->setConf($conf);
		if ($this->datas['gzip'] && !empty($this->conf['gizp'])):
			#客户端支持gzip压缩
			ob_start('ob_gzhandler');
		else:
			ob_start();
		endif;
		set_exception_handler(function ($exception) {
			include __DIR__ . DIRECTORY_SEPARATOR . 'exception.php';
			exit;
		});
		spl_autoload_register(fn($m) => $this->autoload_register($m));
	}
	public function autoload_register($class)
	{
		if (str_starts_with($class, 'model\\')):
			return require(
				plugin::parseFile(
					$this->convert_path(XIUNOPHP_PATH . 'class/' . $class . '.php')
				)
			);
		endif;
	}
	public function setConf(array $conf)
	{
		$this->conf = $conf;
		if (!empty($conf['tmp_path'])):
			if (str_starts_with($conf['tmp_path'], APP_PATH)):
				$this->datas['tmp_path'] = realpath($conf['tmp_path']) . DIRECTORY_SEPARATOR;
			else:
				$this->datas['tmp_path'] = realpath(APP_PATH . $conf['tmp_path']) . DIRECTORY_SEPARATOR;
			endif;
		endif;
		$this->read_cookie_data($conf);
	}
	public function read_cookie_data($conf = array())
	{
		if (empty($this->datas['cookies'])):
			$this->datas['ip'] = $this->get_client_ip();
			$this->datas['cookie_prefix'] = $conf['cookie_prefix'] ?? 'bbs_';
			$this->datas['cookie_domain'] = $conf['cookie_domain'] ?? '';
			$this->datas['cookie_path'] = APP_SITE;
			ini_set('session.name', $this->datas['cookie_prefix'] . 'sid');
			ini_set('session.use_cookies', 'On');
			ini_set('session.use_only_cookies', 'On');
			ini_set('session.cookie_domain', '');
			ini_set('session.cookie_path', '');	// 为空则表示当前目录和子目录
			ini_set('session.cookie_secure', 'Off'); // 打开后，只有通过 https 才有效。
			ini_set('session.cookie_lifetime', 86400);
			ini_set('session.cookie_httponly', 'On'); // 打开后 js 获取不到 HTTP 设置的 cookie, 有效防止 XSS，这个对于安全很重要，除非有 BUG，否则不要关闭。
			ini_set('session.gc_maxlifetime', $conf['online_hold_time']);	// 活动时间 $conf['online_hold_time']
			ini_set('session.gc_probability', 1); 	// 垃圾回收概率 = gc_probability/gc_divisor
			ini_set('session.gc_divisor', 500); 	// 垃圾回收时间 5 秒，在线人数 * 10 
			session_set_save_handler(
				[$this, 'sess_open'],
				[$this, 'sess_close'],
				[$this, 'sess_read'],
				[$this, 'sess_write'],
				[$this, 'sess_destroy'],
				[$this, 'sess_gc']
			);
			// register_shutdown_function 会丢失当前目录，需要 chdir(APP_PATH)
			// 这个比须有，否则 ZEND 会提前释放 $db 资源
			register_shutdown_function('session_write_close');
			$len = strlen($this->datas['cookie_prefix']);
			foreach ($_COOKIE as $k => $v):
				if (str_starts_with($k, $this->datas['cookie_prefix'])):
					$k = substr($k,$len);
					$this->datas['cookies'][$k] = $v;
				endif;
			endforeach;
		endif;
	}
	/**
	 * session打开
	 */
	public function sess_open()
	{
		return true;
	}
	/**
	 * session关闭
	 */
	public function sess_close()
	{
		return true;
	}
	/**
	 * sessoin 读取数据
	 */
	public function sess_read($sid)
	{
		if (empty($sid)) {
			// 查找刚才是不是已经插入一条了？  如果相隔时间特别短，并且 data 为空，则删除。
			// 测试是否支持 cookie，如果不支持 cookie，则不生成 sid
			$sid = session_id();
			$this->sess_new($sid);
			return '';
		}
		$arr = MyDB::t('session')->whereFirst(['sid' => $sid]);
		if (empty($arr)) {
			$this->sess_new($sid);
			return '';
		}
		if ($arr['bigdata'] == 1) {
			$arr2 = MyDB::t('session_data')->whereFirst(['sid' => $sid]);
			$arr['data'] = $arr2['data'];
		}
		$this->datas['session'] = $arr;
		return $arr ? $arr['data'] : '';
	}
	/**
	 * sessoin 写入数据
	 */
	public function sess_write($sid, $data)
	{
		$uid = _SESSION('uid');
		$fid = _SESSION('fid');
		unset($_SESSION['uid']);
		unset($_SESSION['fid']);
		if ($data) {
			$data = session_encode();
		}
		chdir(APP_PATH);
		$url = $_SERVER['REQUEST_URI_NO_PATH'] ?? '';
		if (strlen($url) > 32):
			$url = substr($url, 0, 32);
		endif;
		$agent = $_SERVER['HTTP_USER_AGENT'];
		$arr = array(
			'uid' => $uid,
			'fid' => $fid,
			'url' => $url,
			'last_date' => $_SERVER['REQUEST_TIME'],
			'data' => $data,
			'ip' => ip2long($this->datas['ip']),
			'useragent' => $agent,
			'bigdata' => 0,
		);
		// 开启 session 延迟更新，减轻压力，会导致不重要的数据(useragent,url)显示有些延迟，单位为秒。
		$session_delay_update_on = !empty($this->conf['session_delay_update']) && $_SERVER['REQUEST_TIME'] - $this->datas['session']['last_date'] < $this->conf['session_delay_update'];
		if ($session_delay_update_on) {
			unset($arr['fid']);
			unset($arr['url']);
			unset($arr['last_date']);
		}
		// 判断数据是否超长
		$len = strlen($data);
		$where =  array('sid' => $sid);
		if ($len > 255 && $this->datas['session']['bigdata'] == 0) {
			#db_insert('session_data', array('sid'=>$sid));
			MyDB::t('session_data')->insert_json($where);
		}
		if ($len <= 255) {
			$update = array_diff_value($arr, $this->datas['session']);
			MyDB::t('session')->update_by_where($update, $where);
			if (!empty($this->datas['session']) && $this->datas['session']['bigdata'] == 1) {
				#db_delete('session_data', array('sid'=>$sid));
				MyDB::t('session_data')->delete_by_where($where);
			}
		} else {
			$arr['data'] = '';
			$arr['bigdata'] = 1;
			$update = array_diff_value($arr, $this->datas['session']);

			$update and MyDB::t('session')->update_by_where($update, $where);
			#db_update('session', array('sid'=>$sid), $update);

			$arr2 = array('data' => $data, 'last_date' => $_SERVER['REQUEST_TIME']);
			if ($session_delay_update_on) unset($arr2['last_date']);

			$update2 = array_diff_value($arr2, $this->datas['session']);
			$update2 and MyDB::t('session_data')->update_by_where($update2, $where);

			#db_update('session_data', array('sid'=>$sid), $update2);
		}
		return TRUE;
	}

	public function sess_destroy($sid)
	{
		$where = array('sid' => $sid);
		MyDB::t('session')->delete_by_where($where);
		MyDB::t('session_data')->delete_by_where($where);
		return TRUE;
	}

	public function sess_gc($maxlifetime)
	{
		// echo "sess_gc($maxlifetime) \r\n";
		$expiry = $_SERVER['REQUEST_TIME'] - $maxlifetime;
		$where = array('<last_date' => $expiry);
		MyDB::t('session')->delete_by_where($where);
		MyDB::t('session_data')->delete_by_where($where);
		//db_delete('session', array('last_date' => array('<' => $expiry)));
		//db_delete('session_data', array('last_date' => array('<' => $expiry)));
		return TRUE;
	}
	function sess_new($sid)
	{

		$agent = $_SERVER['HTTP_USER_AGENT'];
		$longip = ip2long($this->datas['ip']);

		/**
		 * 未知作用
		 */
		$cookie_test = _COOKIE('cookie_test');
		if ($cookie_test) {
			$cookie_test_decode = xn_decrypt($cookie_test, $this->conf['auth_key']);
			$this->datas['g_session_invalid'] = ($cookie_test_decode != md5($agent . $longip));
			$this->set_cookies_raw('cookie_test', '');
		} else {
			$cookie_test = xn_encrypt(md5($agent . $longip), $this->conf['auth_key']);
			$this->set_cookies_raw('cookie_test', $cookie_test, $_SERVER['REQUEST_TIME'] + 86400, '');
			$this->datas['g_session_invalid'] = FALSE;
			return;
		}

		// 可能会暴涨
		$url = $_SERVER['REQUEST_URI_NO_PATH'] ?? '';
		if (strlen($url) > 32):
			$url = substr($url, 0, 32);
		endif;
		$arr = array(
			'sid' => $sid,
			'uid' => 0,
			'fid' => 0,
			'url' => $url,
			'last_date' => $_SERVER['REQUEST_TIME'],
			'data' => '',
			'ip' => $longip,
			'useragent' => $agent,
			'bigdata' => 0,
		);
		$this->datas['session'] = $arr;
		$where = ['sid' => $sid];
		if (empty(MyDB::t('session')->whereCount($where))):
			MyDB::t('session')->insert_json($arr);
		else:
			MyDB::t('session')->update_by_where($arr, $where);
		endif;
	}
	public function get_client_ip()
	{
		$ip = '127.0.0.1';
		if (empty($this->conf['cdn_on'])) {
			$ip = $_SERVER['REMOTE_ADDR'];
		} else {
			if (isset($_SERVER['HTTP_CDN_SRC_IP'])) {
				$ip = $_SERVER['HTTP_CDN_SRC_IP'];
			} elseif (isset($_SERVER['HTTP_CLIENTIP'])) {
				$ip = $_SERVER['HTTP_CLIENTIP'];
			} elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
				$ip = $_SERVER['HTTP_CLIENT_IP'];
			} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
				$arr = array_filter(explode(',', $ip));
				$ip = trim(end($arr));
			} else {
				$ip = $_SERVER['REMOTE_ADDR'];
			}
		}
		return long2ip(ip2long($ip));
	}
	public  function sess_restart()
	{
		$data = $this->sess_read($this->datas['sid']);
		session_decode($data); // 直接存入了 $_SESSION
	}
	/**
	 * php 默认的 session 采用文件存储，并且使用 flock() 文件锁避免并发访问不出问题（实际上还是无法解决业务层的并发读后再写入）
	 * 自定义的 session 采用数据表来存储，同样无法解决业务层并发请求问题。
	 * xiuno.js $.each_sync() 串行化并发请求，可以避免客户端并发访问导致的 session 写入问题。
	 */
	public function sess_start()
	{
		session_start();
		$this->datas['sid'] = session_id();
		return $this->datas['sid'];
	}
	public function set_cookies_raw(string $name, mixed $data, int $time = 0)
	{
		if (empty($data)):
			$time = $_SERVER['REQUEST_TIME'] - 86400;
			unset($this->datas['cookies'][$name]);
		else:
			$this->datas['cookies'][$name] = $data;
		endif;
		return setcookie(
			$this->datas['cookie_prefix'].$name,
			$data,
			array(
				'expires' => $time,
				'path' => APP_SITE,
				'domain' => $this->datas['cookie_domain'],
				'secure' => $this->datas['https'] ? true : false,
				'httponly' => true,
				'samesite' => 'Strict',
			)
		);
	}
	public function cache_load()
	{
		$cachelist = array(
			'grouplist' => MyDB::t('group')->selectAll(MyDB::ORDER(['gid' => 'desc'])),
			'forumlist' => ''
		);
	}
	public function forumcache()
	{
		$uids = [];
		foreach (MyDB::t('forum')->select(MyDB::ORDER(['rank' => 'desc'])) as $forum):
			$forum['create_date_fmt'] = date('Y-n-j', $forum['create_date']);
			$forum['icon_url'] = $forum['icon'] ? $this->conf['upload_url'] . "forum/$forum[fid].png" : 'view/img/forum.png';
			$forum['accesslist'] = $forum['accesson'] ? forum_access_find_by_fid($forum['fid']) : array();
			$forum['modlist'] = array();
			if ($forum['moduids']) {
				$forum['moduids'] = explode(',', trim($forum['moduids']));
				if (!empty($forum['moduids'])):
					array_push($uids, ...$forum['moduids']);
				endif;
				$modlist = user_find_by_uids($forum['moduids']);
				foreach ($modlist as &$mod) $mod = user_safe_info($mod);
				$forum['modlist'] = $modlist;
			}
			$forumlist[$forum['fid']] = $forum;
		endforeach;
		$uids = array_unique($uids);
		if (!empty($uids)):
			$userlist = MyDB::t('user')->whereAll(
				array('uid' => $uids),
				'',
				array('username', 'uid')
			);
			if (!empty($userlist)):
				$userlist = array_column($userlist, null, 'uid');
			endif;
		endif;
		foreach (MyDB::t('forum_access')->select() ?? array() as $value):
			if (!empty($forumlist[$value['fid']]['moduids'])):
				foreach ($forumlist[$value['fid']]['moduids'] as $v):
					if (isset($userlist[$v])):
						$forumlist[$value['fid']]['modlist'][$v] = $userlist[$v];
					endif;
				endforeach;
			endif;
			$forumlist[$value['fid']]['accesslist'][$value['gid']] = $value;
		endforeach;
	}


	public static function conf(string $name,mixed $defalut='')
	{
		return self::app()->conf[$name] ?? $defalut;
	}
	public static function path(string $name = ''): string
	{
		return self::convert_path(APP_PATH . $name);
	}
	public static function site(string $name = ''): string
	{
		return APP_SITE . $name;
	}
	public static function tmp_path(string $name = '')
	{
		return self::convert_path(self::$_app['tmp_path'] . $name);
	}
	public static function app()
	{
		return self::$_app;
	}
	public function offsetExists(mixed $offset): bool
	{
		return isset($this->datas[$offset]);
	}
	public function offsetGet(mixed $offset): mixed
	{
		return $this->datas[$offset] ?? '';
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
		$this->datas[$offset] = $value;
	}

	public function offsetUnset(mixed $offset): void
	{
		unset($this->datas[$offset]);
	}
	/**
	 * 格式化地址为标准绝对地址
	 */
	public static function convert_path(string|array $path = ''): string
	{
		if (is_array($path)) return implode(DIRECTORY_SEPARATOR, $path);
		if (empty($path)):
			return '';
		else:
			if (str_starts_with($path, 'phar://')):
				return str_replace('\\', '/', $path);
			endif;
			return str_replace(DIRECTORY_SEPARATOR == '/' ? '\\' : '/', DIRECTORY_SEPARATOR, $path);
		endif;
	}
	/**
	 * 格式化地址为标准网络地址
	 */
	public static function convert_site(string $path): string
	{
		if (str_starts_with($path, APP_PATH)):
			$path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $path);
		endif;
		$path = str_replace('\\', '/', $path);
		return $path;
	}
	/**
	 * 隐藏网站真实父目录
	 */
	public static function convert_safe_path(string|array $str): string
	{
		if (is_string($str)):
			if (str_contains($str, 'phar/')):
				$str = \strstr($str, 'phar/');
			else:
				$str = str_replace(APP_PATH, './', $str);
				$str = str_replace(dirname(APP_PATH), '~', $str);
				$str = str_replace('\\', '/', $str);
			endif;
			return $str;
		elseif (is_array($str)):
			return implode(',', array_map(fn($v) => self::convert_safe_path($v), $str));
		endif;
		return $str;
	}
	public static function write_data_file(string $file, mixed $data)
	{
		$filepath = self::tmp_path('data/' . $file . '.php');
		if (is_array($data)):
			$data = '<?php' . PHP_EOL . 'return ' . var_export($data, true) . ';';
		endif;
		self::create_dir(dirname($filepath));
		file_put_contents($filepath, $data);
	}
	public static function get_data_file(string $file)
	{
		$filepath = self::tmp_path('data/' . $file . '.php');
		if (is_file($filepath)):
			return include($filepath);
		endif;
		return array();
	}
	public static function delete_data_file(string $file)
	{
		$filepath = self::tmp_path('data/' . $file . '.php');
		if (is_file($filepath)):
			@unlink($filepath);
		endif;
	}
	public static function clear_data_file()
	{
		$path = self::tmp_path('data/');
		if (is_dir($path)):
			foreach (scandir($path) as $file):
				if (str_ends_with($file, '.php')):
					@unlink($path . $file);
				endif;
			endforeach;
		endif;
	}
	public static function create_dir($dir)
	{
		if (!is_dir($dir)):
			mkdir($dir, 0755, true);
		endif;
	}
	public static function remove_file($dir, $bool)
	{
		if (!is_dir($dir)) return;
		foreach (scandir($dir) as $file):
			$file = trim($file, '.');
			if (empty($file)):
				continue;
			endif;
			if (is_file($dir . $file)):
				@unlink($dir . $file);
			elseif ($bool && is_dir($dir . $file)):
				$newdir = $dir . $file . DIRECTORY_SEPARATOR;
				self::remove_file($newdir, $bool);
				rmdir($newdir);
			endif;
		endforeach;
	}
	public static function http_location($url)
	{
		while (ob_get_level() > 0):
			@ob_end_clean();
		endwhile;
		if (!str_starts_with($url, 'http')):
			header('Location:' . $url);
		else:
			header('Location:' . self::site($url));
		endif;
		exit;
	}
	/**
	 * 日志输出
	 */
	public static function xn_log($s, $file = 'error')
	{
		if (DEBUG == 0 && strpos($file, 'error') === FALSE) return;
		$ip = $_SERVER['ip'];
		$uid = intval(G('uid')); // xiunophp 未定义 $uid
		$day = date('Ym', $_SERVER['REQUEST_TIME']); // 按照月存放，否则 Ymd 目录太多。
		$mtime = date('Y-m-d H:i:s'); // 默认值为 time()
		$url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		$s = preg_replace('/[\n\r\t]+/', ' ', $s);
		$s = '<?php exit;?>	' . $mtime . '	' . $ip . '	' . $url . '	' . $uid . '	' . $s . PHP_EOL;
		$logpath = MyApp::path('log/' . $day . '/' . $file . '.php');
		MyApp::create_dir(dirname($logpath));
		@error_log($s, 3, $logpath);
	}

	/**
	 * 发送邮件
	 *
	 * @param array $smtp
	 * @param string $username
	 * @param string $email
	 * @param string $subject
	 * @param string $message
	 * @param string $charset
	 */
	public static function xn_send_mail(array $smtp, string $username, string $email, string $subject, string $message, string $charset = 'UTF-8')
	{
		if (!class_exists('PHPMailer\PHPMailer\PHPMailer', false)):
			include(self::convert_path('phar://' . XIUNOPHP_PATH . 'class/phar/PHPMailer.phar/autoload.php'));
		endif;
		$mail             = new \PHPMailer\PHPMailer\PHPMailer;
		$mail->setLanguage('zh-cn'); //繁体 zh-tw
		$mail->IsSMTP();
		$mail->IsHTML(TRUE);
		$mail->SMTPDebug  = \PHPMailer\PHPMailer\SMTP::DEBUG_OFF;
		$mail->SMTPAuth   = TRUE;
		$mail->Host       = $smtp['host'];
		$mail->Port       = $smtp['port'];
		$mail->Username   = $smtp['user'];
		$mail->Password   = $smtp['pass'];
		$mail->Timeout    = 5;
		$mail->CharSet    = $charset;
		$mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
		$mail->Encoding   = 'base64';
		$mail->setFrom($smtp['email'], $username);
		$mail->addReplyTo($smtp['email'], $email);
		$mail->Subject    = $subject;
		$mail->AltBody    = $message;
		$message          = str_replace("\\", '', $message);
		$mail->msgHTML($message);
		$mail->addAddress($email, $username);
		if (!$mail->send()):
			return xn_error(-1, $mail->ErrorInfo);
		endif;
		return TRUE;
	}

	/**
	 * 注册路由
	 */
	private function routerExport(): void
	{
		$query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
		$query = urldecode($query);
		$scriptArr = explode('&', $query);
		$this->datas['querydata'] = array(
			'query' => $query,
			'param' => array(),
		);
		foreach ($scriptArr as $index => $data):
			$arr = explode('=', $data);
			$key = array_shift($arr);
			$key = trim(urldecode($key));
			$value = array_shift($arr) ?? '';
			$value = trim(urldecode($value));
			if (!empty($key)):
				if (empty($value) && $index == 0):
					$this->routerReadPath(trim($key));
				elseif (is_numeric($value)):
					$this->datas['querydata']['param'][$key] = intval($value);
				elseif (!is_null($value)):
					$this->datas['querydata']['param'][$key] = $value;
				endif;
			endif;
		endforeach;
		if (empty($this->datas['querydata']['module'])):
			$this->datas['querydata']['module'] = 'index';
		endif;
	}
	private function routerReadPath(string $path): void
	{
		$this->datas['querydata']['path'] = $path;
		$path = trim($path, '.\\/');
		if (!empty($path)):
			$path = strtolower($path);
			$pathlist = explode('/', $path);
			$last = array_pop($pathlist);
			if (!empty($last)):
				$extension = pathinfo($last, PATHINFO_EXTENSION);
				if ($extension):
					$filename = pathinfo($last, PATHINFO_FILENAME);
					$filename = trim($filename);
					$filename = trim($filename, '-');
					$pathlist[] = $filename;
					#$router_list = explode('-', trim($filename));
					#array_push($pathlist, ...$router_list);
					$this->datas['querydata']['filename'] = $filename;
					$this->datas['querydata']['extension'] = $extension;
				else:
					$pathlist[] = $last;
				endif;
			endif;
			$this->routerSetValue($pathlist);
		endif;
	}
	private function routerIsUUID(string $uuid): bool
	{
		if (preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){3}-[a-f\d]{12}$/', $uuid)):
			if (empty($this->datas['querydata']['uuid'])):
				$this->datas['querydata']['uuid'] = $uuid;
			endif;
			return true;
		endif;
		return false;
	}
	private function routerSetValue(array $list): void
	{
		if (!empty($list)):
			foreach ($list as $key => $value):
				$value = is_numeric($value) ? intval($value) : trim(basename($value), ' 	$@^*()=+<>\'"`#?&;%./\\~');
				if ($key == 0 && empty($this->datas['querydata']['module'])):
					if (empty($value)):
						$this->datas['querydata']['module'] = 'index';
						continue;
					endif;
					$valueLen = strlen($value);
					$this->datas['querydata']['value'] = $value;
					if ($valueLen == 36 && $this->routerIsUUID($value)):
						$this->datas['querydata']['module'] = 'uuid';
					elseif (is_numeric($value)):
						$this->datas['querydata']['module'] = 'id';
					else:
						$newarr = explode(':', $value);
						$value = array_pop($newarr);
						$this->datas['querydata']['plugin'] = array_pop($newarr);
						if (stripos($value, '-') !== false && count($list) == 1):
							$this->routerSetValue(explode('-', $value));
						elseif (strlen($value) > 32):
							$this->datas['querydata']['module'] = 'text';
						else:
							$this->datas['querydata']['module'] = $value;
						endif;
					endif;
					continue;
				endif;
				if (empty($value)) continue;
				$this->datas['querydata'][] = $value;
			endforeach;
		endif;
	}
	/**
	 * 获取myapp中data数据
	 */
	public static function data(string $name = ''): mixed
	{
		if (!empty($name)):
			return self::app()->datas[$name] ?? '';
		endif;
		return self::app()->datas;
	}
	/**
	 * 获取myapp中data[querydata]数据
	 */
	public static function value(mixed $key, string $defalut = ''): mixed
	{
		return self::app()->datas['querydata'][$key] ?? $defalut;
	}
	/**
	 * 设置myapp中data[querydata]数据
	 */
	public static function setValue(string $key, mixed $value): void
	{
		self::app()->datas['querydata'][$key] = $value;
	}
	/**
	 * 获取$_GET
	 */
	public static function param(mixed $key, string $defalut = ''): mixed
	{
		if (is_int($key)) return self::value($key);
		return self::app()->datas['querydata']['param'][$key] ?? $defalut;
	}
	/**
	 * 获取$_POST
	 */
	public static function post(string $key, string $defalut = ''): mixed
	{
		return $_POST[$key] ?? $defalut;
	}
	/**
	 * 获取HEADER标记
	 */
	public static function head(string $name,mixed $defalut=''):string|int
	{
		$name = 'HTTP_'.strtoupper(str_replace('-','_',$name));
		$value =  $_SERVER[$name] ?? $defalut;
		if(is_numeric($value)):
			return intval($value);
		endif;
		return $value;
	}
	/**
	 * 返回合法URL参数param
	 */
	public function get_url_param(string|array $param = array(), bool $width = false): string
	{
		if (empty($param)):
			$param = array();
			if ($width):
				$param = $this->datas['querydata']['param'];
			endif;
		else:
			if (!empty($param) && is_string($param)):
				parse_str($param, $param);
			else:
				$param = array();
			endif;
			if ($width):
				$param = array_merge($this->datas['querydata']['param'], $param);
			endif;
		endif;
		return http_build_query($param, "", null, PHP_QUERY_RFC3986);
	}
	/**
	 * 返回相对URL
	 */
	public function get_url_href(string|array $router = 'index', string|array $param = array(), bool $width = false): string
	{
		if (empty($router)):
			$router = '';
			if ($this->datas['rewriteMode'] && empty($router) && $this->datas['extension'] != 'php'):
				#设置 如index.html默认地址
				$router = 'index.' . $this->datas['extension'];
			endif;
		else:
			if (is_array($router)):
				$router = implode('/', $router);
			#$router .= '.' . $this->datas['extension'];
			else:
				$router = trim($router, '-\/\\.');
			endif;
			$exception = pathinfo($router, PATHINFO_EXTENSION);
			if (empty($exception)):
				$router .= '.' . $this->datas['extension'];
			endif;
		endif;
		$param = $this->get_url_param($param, $width);
		#index.php 为引导文件
		if ($this->datas['rewriteopen']&&$this->datas['rewriteMode']):
			#是否支持伪静态重写
			if (!empty($param)):
				if (empty($router)):
					$router = '?' . $param;
				else:
					$router .= '&' . $param;
				endif;
			endif;
			return $this->datas['rewriteroot']. $router;
		endif;
		if (!empty($param)):
			if (empty($router)):
				$router = $param;
			else:
				$router .= '&' . $param;
			endif;
		endif;
		if (!empty($router)):
			$router = '?' . $router;
		endif;
		if ($this->datas['rewriteopen']):
			return $this->datas['rewriteroot'].$router;
		endif;
		return $_SERVER['PHP_SELF'] . $router;
	}
	public static function url(string|array $router = 'index', string|array $param = array(), bool $width = false): string
	{
		return self::app()->get_url_href($router, $param, $width);
	}
	public static function purl(string|array $router = '', string|array $param = array(), bool $width = false)
	{
		return self::app()->get_url_href(self::value('module') . '/' . $router, $param, $width);
	}
	public static function cookies($name,?string $value=null,int $time=0)
	{
		if(isset($value)):
			self::app()->set_cookies_raw($name,$value,$time);
		else:
			return self::app()->datas['cookies'][$name] ?? NULL;
		endif;
	}
	public static function scss(string $link,string $href='')
	{
		if(!str_starts_with($link,APP_PATH)):
			$link = self::path('view/scss/'.$link);
		endif;
		if(empty($href)):
			$href = self::path('view/css/'.pathinfo($link,PATHINFO_FILENAME).'.css');
		endif;
		if(is_file($href)):
			if(!defined('DEBUG') || !DEBUG):
				return self::convert_site($href);
			elseif(filemtime($link)<filemtime($href)):
				return self::convert_site($href); 
			endif;
		endif;
		if(model\tool::scss_write($link,$href)):
			return self::convert_site($href);
		endif;
		return '';
	}
	public static function scsslink(string $link,string $href='')
	{
		$url = self::scss($link,$href);
		if(empty($url)):
			return '';
		endif;
		return '<link rel="stylesheet" type="text/css" href="' . $url . '" />';

	}
	
	/**
	 * 迭代扫描目录文件
	 */
	public static function scanDIR(string $dir, string|bool $replaceDir = ''): array
	{
		$files = array();
		$dir = self::convert_path($dir);
		if (!str_ends_with($dir, DIRECTORY_SEPARATOR)):
			$dir .= DIRECTORY_SEPARATOR;
		endif;
		$basedir = empty($replaceDir) ? $dir : $replaceDir;
		if (is_dir($dir)):
			foreach (scandir($dir) as $file) :
				if ($file != '.' && $file != '..') :
					if (\is_file($dir . $file)) :
						$filepath = str_replace($basedir, '', $dir . $file);
						$files[] = str_replace('\\', '/', $filepath);
					else :
						$newpath = $dir . $file . DIRECTORY_SEPARATOR;
						if ($replaceDir !== false || is_dir($newpath)) :
							array_push($files, ...self::scanDIR($newpath, $basedir));
						endif;
					endif;
				endif;
			endforeach;
		endif;
		return $files;
	}
}
