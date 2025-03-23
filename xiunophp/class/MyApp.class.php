<?php
class MyApp implements \ArrayAccess
{
	public static $_app;
	public array $conf;
	public array $data = array();
	public function __construct($conf = array())
	{
		self::$_app = $this;
		define('APP_SITE', $this->convert_site(APP_PATH));
		$this->setConf($conf);
		set_exception_handler(function ($exception) {
			include __DIR__ . DIRECTORY_SEPARATOR . 'exception.php';
			exit;
		});
	}
	public function setConf(array $conf)
	{
		$this->conf = $conf;
		if (!empty($conf['tmp_path'])):
			if (str_starts_with($conf['tmp_path'], APP_PATH)):
				$this->data['tmp_path'] = realpath($conf['tmp_path']) . DIRECTORY_SEPARATOR;
			else:
				$this->data['tmp_path'] = realpath(APP_PATH . $conf['tmp_path']) . DIRECTORY_SEPARATOR;
			endif;
		endif;
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
		return isset($this->data[$offset]);
	}
	public function offsetGet(mixed $offset): mixed
	{
		return $this->data[$offset] ?? '';
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
		$this->data[$offset] = $value;
	}

	public function offsetUnset(mixed $offset): void
	{
		unset($this->data[$offset]);
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
	public static function xn_send_mail(array $smtp,string $username,string $email,string $subject,string $message, string $charset = 'UTF-8') {
		if(!class_exists('PHPMailer\PHPMailer\PHPMailer',false)):
			include(self::convert_path('phar://'.XIUNOPHP_PATH.'class/phar/PHPMailer.phar/autoload.php'));
		endif;
		$mail             = new \PHPMailer\PHPMailer\PHPMailer;
		$mail->setLanguage('zh-cn');//繁体 zh-tw
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
		$message          = str_replace("\\",'',$message);
		$mail->msgHTML($message);
		$mail->addAddress($email, $username);
		if(!$mail->send()):
			return xn_error(-1, $mail->ErrorInfo);
		endif;
		return TRUE;
	}
}
