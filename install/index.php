<?php

define('DEBUG', 2);
define('APP_PATH', dirname(__DIR__) . '/');
define('INSTALL_PATH', __DIR__.'/');

define('MESSAGE_HTM_PATH', INSTALL_PATH . 'view/htm/message.htm');

// 切换到上一级目录，操作很方便。
$conf = (include APP_PATH . 'conf/conf.default.php');
include APP_PATH . 'xiunophp/xiunophp.php';
include APP_PATH . 'model/misc.func.php';
include APP_PATH . 'model/plugin.func.php';
include APP_PATH . 'model/user.func.php';
include APP_PATH . 'model/group.func.php';
include APP_PATH . 'model/form.func.php';
include APP_PATH . 'model/forum.func.php';
include INSTALL_PATH . 'install.func.php';
MyApp::addLang('bbs.php');
$lang = MyApp::addLang('bbs_install.php');
//$_SERVER['lang'] = $lang;
$conf['tmp_path'] = APP_PATH . $conf['tmp_path'];
$action = param('action');
// 安装初始化检测,放这里
is_file(APP_PATH . 'conf/conf.php') and DEBUG != 2 and message(0, jump(MyApp::Lang('installed_tips'), '../'));

// 从 cookie 中获取数据，默认为中文
$_lang = param('lang', 'zh-cn');



// 第一步，阅读
if (empty($action)) {

	if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		$input = array();
		$input['lang'] = form_select('lang', array('zh-cn' => '简体中文', 'zh-tw' => '正體中文', 'en-us' => 'English', 'ru-ru' => 'Русский', 'th-th' => 'ไทย'), $conf['lang']);

		// 修改 conf.php
		include INSTALL_PATH . 'view/htm/index.htm';
	} else {
		$_lang = param('lang');
		!in_array($_lang, array('zh-cn', 'zh-tw', 'en-us', 'ru-ru', 'th-th')) and $_lang = 'zh-cn';
		setcookie('lang', $_lang);

		//$conf['lang'] = $_lang;
		//xn_copy(APP_PATH.'./conf/conf.default.php', APP_PATH.'./conf/conf.backup.php');
		//$r = file_replace_var(APP_PATH.'conf/conf.default.php', array('lang'=>$_lang));
		//$r === FALSE AND message(-1, jump(MyApp::Lang('please_set_conf_file_writable'), ''));

		http_location('index.php?action=license');
	}
} elseif ($action == 'license') {


	// 设置到 cookie

	include INSTALL_PATH . 'view/htm/license.htm';
} elseif ($action == 'env') {

	if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		$succeed = 1;
		$env = $write = array();
		get_env($env, $write);
		include INSTALL_PATH . 'view/htm/env.htm';
	} else {
	}
} elseif ($action == 'db') {

	if ($_SERVER['REQUEST_METHOD'] == 'GET') {

		$succeed = 1;
		$mysql_support = function_exists('mysqli_connect');
		$pdo_mysql_support = extension_loaded('pdo_mysql');
		$myisam_support = extension_loaded('pdo_mysql');
		$innodb_support = extension_loaded('pdo_mysql');

		(!$mysql_support && !$pdo_mysql_support) and message(-1, MyApp::Lang('evn_not_support_php_mysql'));

		include INSTALL_PATH . 'view/htm/db.htm';
	} else {

		$type = param('type');
		$engine = param('engine');
		$host = param('host');
		$name = param('name');
		$user = param('user');
		$password = param('password');
		$force = param('force');

		$adminemail = param('adminemail');
		$adminuser = param('adminuser');
		$adminpass = param('adminpass');

		empty($host) and message('host', MyApp::Lang('dbhost_is_empty'));
		empty($name) and message('name', MyApp::Lang('dbname_is_empty'));
		empty($user) and message('user', MyApp::Lang('dbuser_is_empty'));
		empty($adminpass) and message('adminpass', MyApp::Lang('adminuser_is_empty'));
		empty($adminemail) and message('adminemail', MyApp::Lang('adminpass_is_empty'));



		// 设置超时尽量短一些
		//set_time_limit(60);
		ini_set('mysql.connect_timeout',  5);
		ini_set('default_socket_timeout', 5);

		$conf['db']['type'] = $type;
		$conf['db']['mysql']['master']['host'] = $host;
		$conf['db']['mysql']['master']['name'] = $name;
		$conf['db']['mysql']['master']['user'] = $user;
		$conf['db']['mysql']['master']['password'] = $password;
		$conf['db']['mysql']['master']['engine'] = $engine;
		$conf['db']['pdo_mysql']['master']['host'] = $host;
		$conf['db']['pdo_mysql']['master']['name'] = $name;
		$conf['db']['pdo_mysql']['master']['user'] = $user;
		$conf['db']['pdo_mysql']['master']['password'] = $password;
		$conf['db']['pdo_mysql']['master']['engine'] = $engine;

		$newconf = $conf['db'][$conf['db']['type']];
		$newconf_name = $newconf['master']['name'];
		unset($newconf['master']['name']);
		$db = MyDB::app();
		$newconf['error'] = true;
		$db->setConfig($newconf, $conf['db']['type'] == 'mysql' ? null : $conf['db']['type']);
		try {
			$link = $db->connect_master();
		} catch (\Exception $e) {
			@ob_clean();
			message(-1, $e->getMessage() . '.(errno:' . $e->getCode() . ')');
			exit;
		}
		$db->connect();
		$database = $link->querySQL('SHOW DATABASES', 2);
		if (empty($database) || in_array(!$newconf_name, array_column($database, 0))):
			try {
				#创建数据库
				$link->execSQL('CREATE DATABASE ' . $newconf_name);
			} catch (\Exception $e) {
				@ob_clean();
				message(-1, $e->getMessage() . '.(errno:' . $e->getCode() . ')');
				exit;
			}
		endif;
		$link->execSQL('use ' . $newconf_name);
		// 设置引擎的类型
		if ($engine == 'innodb'):
			if(!$db->is_support_innodb()):
				$engine = 'myisam';
			endif;
		endif;
		$conf['db']['pdo_mysql']['master']['engine'] = $engine;
		$conf['db']['mysql']['master']['engine'] = $engine;
		$db->conf['master']['engine'] = $engine;
		
		$conf['cache']['mysql']['db'] = $db; // 这里直接传 $db，复用 $db；如果传配置文件，会产生新链接。
		MyApp::app()->datas['cacheobj'] = $cache = !empty($conf['cache']) ? cache_new($conf['cache']) : NULL;


		// 连接成功以后，开始建表，导数据。

		install_sql_file(INSTALL_PATH . 'install.sql');

		// 初始化
		copy(APP_PATH . 'conf/conf.default.php', APP_PATH . 'conf/conf.php');

		// 管理员密码
		$salt = xn_rand(16);
		$password = md5(md5($adminpass) . $salt);
		$update = array('username' => $adminuser, 'email' => $adminemail, 'password' => $password, 'salt' => $salt, 'create_date' =>$_SERVER['REQUEST_TIME'], 'create_ip' => $longip);
		db_update('user', array('uid' => 1), $update);

		$replace = array();
		$replace['db'] = $conf['db'];
		$replace['auth_key'] = xn_rand(64);
		$replace['installed'] = 1;
		file_replace_var(APP_PATH . 'conf/conf.php', $replace);

		// 处理语言包
		group_update(0, array('name' => MyApp::Lang('group_0')));
		group_update(1, array('name' => MyApp::Lang('group_1')));
		group_update(2, array('name' => MyApp::Lang('group_2')));
		group_update(4, array('name' => MyApp::Lang('group_4')));
		group_update(5, array('name' => MyApp::Lang('group_5')));
		group_update(6, array('name' => MyApp::Lang('group_6')));
		group_update(7, array('name' => MyApp::Lang('group_7')));
		group_update(101, array('name' => MyApp::Lang('group_101')));
		group_update(102, array('name' => MyApp::Lang('group_102')));
		group_update(103, array('name' => MyApp::Lang('group_103')));
		group_update(104, array('name' => MyApp::Lang('group_104')));
		group_update(105, array('name' => MyApp::Lang('group_105')));

		forum_update(1, array('name' => MyApp::Lang('default_forum_name'), 'brief' => MyApp::Lang('default_forum_brief')));

		xn_mkdir(APP_PATH . 'upload/tmp', 0777);
		xn_mkdir(APP_PATH . 'upload/attach', 0777);
		xn_mkdir(APP_PATH . 'upload/avatar', 0777);
		xn_mkdir(APP_PATH . 'upload/forum', 0777);

		message(0, jump(MyApp::Lang('conguralation_installed'), '../'));
	}
}
