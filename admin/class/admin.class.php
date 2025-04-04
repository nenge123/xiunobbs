<?php
/**
 * 后台控制函数
 */
class route_admin
{
	public static function plugin_exists($dir)
	{
		!is_word($dir) and MyApp::message(-1, MyApp::Lang('plugin_name_error'));
		$plugin = plugin::get_plugin_json($dir);
		if (empty($plugin)):
			MyApp::message(-1, MyApp::Lang('plugin_not_exists'));
		endif;
		return $plugin;
	}
	public static function plugin_save(array $plugin, string $dir)
	{
		return file_put_contents(plugin::path($dir . '/conf.json'), plugin::json($plugin));
	}

	/**
	 * 读取插件中hook文件
	 */
	public static function plugin_hook(string $dir)
	{
		$hookpaths = plugin::glob_hook($dir);
		$list = array();
		if (is_array($hookpaths)):
			foreach ($hookpaths as $hookpath):
				$list[] = file_name($hookpath);
			endforeach;
		endif;
		return $list;
	}
	public static function plugin_overwrite($dir)
	{
		$path = plugin::path($dir . '/overwrite/');
		$hookpaths = glob($path . '*.*', GLOB_NOSORT);
		$len = strlen($path);
		$list = array();
		if (is_array($hookpaths)):
			foreach ($hookpaths as $hookpath):
				$hookpath = substr($hookpath, $len);
				$list[] = str_replace('\\', '/', $hookpath);
			endforeach;
		endif;
		return $list;
	}
	public static function plugin_route($dir)
	{
		$path = plugin::path($dir . '/route/');
		$hookpaths = glob($path . '*.*', GLOB_NOSORT);
		$len = strlen($path);
		$list = array();
		if (is_array($hookpaths)):
			foreach ($hookpaths as $hookpath):
				$hookpath = substr($hookpath, $len);
				$list[] = str_replace('\\', '/', $hookpath);
			endforeach;
		endif;
		return $list;
	}
	/**
	 * 清空缓存文件
	 */
	public static function clear_tmp()
	{
		self::rmfile(MyApp::tmp_path(), 1);
	}
	public static function rmfile($dir, $bool)
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
				self::rmfile($newdir, $bool);
				rmdir($newdir);
			endif;
		endforeach;
	}
	public static function plugin_lock()
	{
		$route = MyApp::value('module');
		$action = MyApp::value(0);
		!plugin::lock($route . '_' . $action) and MyApp::message(-1, MyApp::Lang('plugin_task_locked'));
	}
	public static function plugin_unlock()
	{
		$route = MyApp::value('module');
		$action = MyApp::value(0);
		plugin::unlock($route . '_' . $action);
	}
	public static function site($dir = ''): string
	{
		return ADMIN_SITE . $dir;
	}
	public static function js_module($name)
	{
		return ' onmethods="loadmodule" module-url="' . route_admin::site('view/js/module/' . $name . '.js') . '"';
	}
	public static function path(string $dir): string
	{
		return MyApp::convert_path(ADMIN_PATH . $dir);
	}
	public static function scss($file)
	{
		return \MyApp::scsslink(route_admin::path('view/scss/' . $file . '.scss'), route_admin::path('view/css/' . $file . '.css'));
	}
	public static function eventStart()
	{
		@ob_clean();
		set_time_limit(0); #脚本没超时限制
		ignore_user_abort(true); #不好说
		while (ob_get_level() > 0):
			@ob_end_clean();
		endwhile; #关闭所有缓冲,IIS实际上有一个顶级缓存fastcgi关不掉,参考上面关掉
		header("X-Accel-Buffering: no");
		header("Content-Type: text/event-stream"); #非常重要
		echo PHP_EOL . PHP_EOL; #重点 每条消息末端必须用两个\r\n隔开
		flush(); #兼容,一般可忽略

	}
	public static function eventMessage($type, $id, $json)
	{
		echo 'event:' . $type . PHP_EOL; #相当于响应side事件
		echo 'id:' . $id . PHP_EOL; #相当于响应id
		echo 'data:' . json_encode($json,) . PHP_EOL; #相当于事件里的event.data
		echo PHP_EOL . PHP_EOL; #重点 每条消息末端必须用两个\r\n隔开
		flush(); #兼容,一般可忽略
	}
	public static function format_post()
	{
		foreach ($_POST as $k => $v):
			if (is_numeric($v)):
				$_POST[$k] = intval($v);
			elseif (is_string($v)):
				if ($v === 'true'):
					$_POST[$k] = true;
				elseif ($v === 'false'):
					$_POST[$k] = false;
				elseif ($v === 'null'):
					$_POST[$k] = null;
				else:
					$_POST[$k] = trim($v);
				endif;
			endif;
		endforeach;
	}
	public static function safe_uids($uids)
	{
		$_userlist = MyDB::t('user')->whereAll(['uid' => $uids], '', array('uid', 'gid'));
		$_userlist = array_column($_userlist, 'gid', 'uid');
		$_userlist = array_filter(
			$_userlist,
			fn($m) => !in_array($m, array(1, 2, 3, 4, 5)),
		);
		return array_keys($_userlist);
	}

	public static function admin_token_set()
	{
		$admin_token = $_SERVER['REQUEST_TIME'];
		$admin_hash = MyApp::encrypt($admin_token);
		// hook admin_token_set_start.php
		MyApp::cookies('admin_token', '**' . $admin_hash, $_SERVER['REQUEST_TIME'] + 3600);
		// hook admin_token_set_end.php
		return $admin_token;
	}
	public static function admin_token_check()
	{
		$admin_token = MyApp::cookies('admin_token');
		if (!empty($admin_token)):
			#MyApp::cookies('admin_token', '');
			#MyApp::message(-1, MyApp::Lang('admin_token_expiry'), array('url'=>MyApp::url('index/login')));
			$admin_token = intval($admin_token);
			// hook admin_token_check_start.php
			// 后台超过 3600 自动退出。
			if ($_SERVER['REQUEST_TIME'] - $admin_token > 3600):
				MyApp::cookies('admin_token', '');
				MyApp::message(-1, MyApp::Lang('admin_token_expiry'), array('url' => MyApp::url('index/login')));
			endif;
			// 超过半小时，重新发新令牌，防止过期
			// More than half an hour, reset a new token, prevent expired
			if ($_SERVER['REQUEST_TIME'] - $admin_token > 1800) :
				$admin_token = route_admin::admin_token_set();
			endif;
			// hook admin_token_check_end.php
			return $admin_token;
		endif;
		return false;
	}
	/**
	 * 后台模板文件
	 */
	public static function tpl_file($name):string
	{
		return self::path('view/htm/'.$name);
	}
	public static function tpl_link($name)
	{
		return plugin::parseFile(self::tpl_file($name));
	}
	/**
	 * 后台页眉
	 *
	 * @return string
	 */
	public static  function tpl_header():string
	{
		return self::tpl_file('header.inc.htm');
	}
	/**
	 * 后台页脚
	 *
	 * @return string
	 */
	public static  function tpl_footer():string
	{
		return self::tpl_file('footer.inc.htm');
	}
}

// hook admin_func_start.php
// bootstrap style
function admin_tab_active($arr, $active)
{
	// hook admin_tab_active_start.php
	$s = '';
	foreach ($arr as $k => $v) {
		$s .= '<a role="button" class="btn btn-secondary' . ($active == $k ? ' active' : '') . '" href="' . $v['url'] . '">' . $v['text'] . '</a>';
	}
	// hook admin_tab_active_end.php
	return $s;
}
// hook admin_func_end.php
