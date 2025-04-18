<?php
!defined('APP_PATH') and exit('Access Denied.');
/*
	XiunoPHP 4.0 只是定义了一些函数和全局变量，方便使用，并没有要求如何组织代码。
	采用静态语言编程风格，有利于 Zend 引擎的编译和 OPCache 缓存，支持 PHP7
	1. 禁止使用 eval(), 正则表达式 e 修饰符
	2. 尽量避免 autoload
	3. 尽量避免 $$var 多重变量
	4. 尽量避免 PHP 高级特性 __call __set __get 等魔术方法，不利于错误排查
	5. 尽量采用函数封装功能，通过前缀区分模块
*/
define('XIUNOPHP_PATH', __DIR__ . DIRECTORY_SEPARATOR);
error_reporting(DEBUG ? E_ALL : 0);
$starttime = $_SERVER['REQUEST_TIME_FLOAT'];
$time = $_SERVER['REQUEST_TIME'];
$useragent = $_SERVER['HTTP_USER_AGENT'];
if (PHP_SAPI === 'cli'):
	echo '不允许命令行访问';
	exit;
endif;
// hook xiunophp_include_before.php
// ----------------------------------------------------------> db cache class
include XIUNOPHP_PATH . 'class/MyApp.php';
// ----------------------------------------------------------> 全局函数
include XIUNOPHP_PATH . 'db.func.php';
include XIUNOPHP_PATH . 'cache.func.php';
include XIUNOPHP_PATH . 'image.func.php';
include XIUNOPHP_PATH . 'array.func.php';
include XIUNOPHP_PATH . 'xn_encrypt.func.php';
include XIUNOPHP_PATH . 'misc.func.php';
// hook xiunophp_include_after.php
empty($conf) and $conf = array('db' => array(), 'cache' => array(), 'tmp_path' => './', 'log_path' => './', 'timezone' => 'Asia/Shanghai');
empty($conf['tmp_path']) and $conf['tmp_path'] = 'tmp';
empty($conf['timezone']) and $conf['timezone'] = 'Asia/Shanghai';
date_default_timezone_set($conf['timezone']);

// 语言包变量
!isset($lang) and $lang = array();

// 全局的错误，非多线程下很方便。
$errno = 0;
$errstr = '';

$myapp = new MyApp($conf);
$ip = MyApp::data('ip');
$longip = MyApp::data('longip');
$longip < 0 and $longip = sprintf("%u", $longip); // fix 32 位 OS 下溢出的问题
// error_handle
// register_shutdown_function('xn_shutdown_handle');
DEBUG and set_error_handler('error_handle', -1);

// 超级全局变量
!empty($_SERVER['HTTP_X_REWRITE_URL']) and $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
!isset($_SERVER['REQUEST_URI']) and $_SERVER['REQUEST_URI'] = '';
$_SERVER['REQUEST_URI'] = str_replace('/index.php?', '/', $_SERVER['REQUEST_URI']); // 兼容 iis6
$_REQUEST = array_merge($_COOKIE, $_POST, $_GET, xn_url_parse($_SERVER['REQUEST_URI']));

// IP 地址
!isset($_SERVER['REMOTE_ADDR']) and $_SERVER['REMOTE_ADDR'] = '';
!isset($_SERVER['SERVER_ADDR']) and $_SERVER['SERVER_ADDR'] = '';

// $_SERVER['REQUEST_METHOD'] === 'PUT' ? @parse_str(file_get_contents('php://input', false , null, -1 , $_SERVER['CONTENT_LENGTH']), $_PUT) : $_PUT = array(); // 不需要支持 PUT
$ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower(trim($_SERVER['HTTP_X_REQUESTED_WITH'])) == 'xmlhttprequest') || param('ajax');
$method = $_SERVER['REQUEST_METHOD'];




// 保存到超级全局变量，防止冲突被覆盖。
$_SERVER['ip'] = $ip;
$_SERVER['longip'] = $longip;
$_SERVER['conf'] = $conf;
$_SERVER['errno'] = $errno;
$_SERVER['errstr'] = $errstr;
$_SERVER['method'] = $method;
$_SERVER['ajax'] = $ajax;




// 初始化 db cache，这里并没有连接，在获取数据的时候会自动连接。
$db = !empty($conf['db']) ? MyDB::create($conf['db']) : NULL;
//$db AND $db->errno AND xn_message(-1, $db->errstr); // 安装的时候检测过了，不必每次都检测。但是要考虑环境移植。

#$conf['cache']['mysql']['db'] = $db; // 这里直接传 $db，复用 $db；如果传配置文件，会产生新链接。
$cache = !empty($conf['cache']) ? cache_new($conf['cache']) : NULL;
MyApp::app()->datas['cacheobj'] = $cache;
#unset($conf['cache']['mysql']['db']); // 用完清除，防止保存到配置文件
//$cache AND $cache->errno AND xn_message(-1, $cache->errstr);
