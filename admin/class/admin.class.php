<?php

class route_admin
{
	public static function plugin_exists($dir)
	{
		!is_word($dir) and message(-1, lang('plugin_name_error'));
		$plugin = plugin::get_plugin_json($dir);
		if (empty($plugin)):
			message(-1, lang('plugin_not_exists'));
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
		!plugin::lock($route . '_' . $action) and message(-1, lang('plugin_task_locked'));
	}
	public static function plugin_unlock()
	{
		$route = MyApp::value('module');
		$action = MyApp::value(0);
		plugin::unlock($route . '_' . $action);
	}
	public static function site($dir = ''): string
	{
		return MyApp::convert_site(ADMIN_PATH . $dir);
	}
	public static function path(string $dir): string
	{
		return MyApp::convert_path(ADMIN_PATH . $dir);
	}
	public static function scss($file)
	{
		return \MyApp::scsslink(route_admin::path('view/scss/' . $file . '.scss'), route_admin::path('view/css/' . $file . '.css'));
	}

	public static function input_format_id($id)
	{
		return preg_replace('/[\[\]]/', '-', $id);
	}
	public static function input_format_lang($name, $lang = null)
	{
		if (empty($lang)):
			return $lang === null ? lang($name) : '';
		endif;
		return lang($lang);
	}
	public static function input_check(string $name, mixed $value = null, ?string $lang = null)
	{
		if (!isset($value)):
			$value = MyApp::conf($name);
		endif;
		$id = self::input_format_id('check-' . $name . '-' . $_SERVER['REQUEST_TIME']);
		return '<label class="form-check-label" for="' . $id . '">' . self::input_format_lang($name, $lang) . '</label><input class="form-check-input" type="checkbox" role="switch" id="' . $id . '" name="' . $name . '" value="1" ' . (empty($value) ? '' : 'checked') . '>';
	}
	public static function input_required_text(string $name, mixed $value = null, ?string $lang = null, $required = 'required')
	{
		if (!isset($value)):
			$value = MyApp::conf($name);
		endif;
		$id = self::input_format_id('text-' . $name . '-' . $_SERVER['REQUEST_TIME']);
		return '<input type="text" class="form-control" id="' . $id . '" name="' . $name . '" value="' . $value . '" ' . $required . '><label for="' . $id . '">' . self::input_format_lang($name, $lang) . '：</label>';
	}
	public static function input_required_number(string $name, mixed $value = null, ?string $lang = null, $required = 'required')
	{
		if (!isset($value)):
			$value = MyApp::conf($name, 0);
		endif;
		$id = self::input_format_id('text-' . $name . '-' . $_SERVER['REQUEST_TIME']);
		return '<input type="number" class="form-control" id="' . $id . '" name="' . $name . '" value="' . $value . '" ' . $required . '><label for="' . $id . '">' . self::input_format_lang($name, $lang) . '：</label>';
	}
	public static function input_required_textarea(string $name, mixed $value = null, ?string $lang = null, $required = 'required')
	{
		if (!isset($value)):
			$value = MyApp::conf($name);
		endif;
		$id = self::input_format_id('textarea-' . $name . '-' . $_SERVER['REQUEST_TIME']);
		return '<textarea type="text" class="form-control" id="' . $id . '" name="' . $name . '" style="min-height:150px;" ' . $required . '>' . htmlentities($value) . '</textarea><label for="' . $id . '">' . self::input_format_lang($name, $lang) . '：</label>';
	}
	public static function input_date_text(string $name, mixed $value = null, ?string $lang = null, $required = '')
	{
		if (!isset($value)):
			$value = MyApp::conf($name);
		endif;
		$id = self::input_format_id('textarea-' . $name . '-' . $_SERVER['REQUEST_TIME']);
		return '<input type="date" class="form-control" id="' . $id . '" name="' . $name . '" value="'.htmlentities($value).'" ' . $required . '/><label for="' . $id . '">' . self::input_format_lang($name, $lang) . '：</label>';
	}
	public static function input_text(string $name, mixed $value = null, ?string $lang = null)
	{
		return self::input_required_text($name, $value, $lang, '');
	}
	public static function input_textarea(string $name, mixed $value = null, ?string $lang = null)
	{
		return self::input_required_textarea($name, $value, $lang, '');
	}
	public static function input_required_select(string $name, mixed $list, mixed $value = null, ?string $lang = null, ?string $required = 'required')
	{
		if (!isset($value)):
			$value = MyApp::conf($name);
		endif;
		$id = self::input_format_id('textarea-' . $name . '-' . $_SERVER['REQUEST_TIME']);
		$lang = empty($lang) ? $name : $lang;
		$html = '<select class="form-select" name="' . $name . '" id="' . $id . '" aria-label="' . $lang . '" ' . $required . '>';
		if (is_int($list)):
			$value = intval($value);
			for ($i = 0; $i <= $list; $i++):
				$html .= '<option value="' . $i . '" ' . ($i == $value ? 'selected' : '') . '>' . lang($lang . '_' . $i) . '</option>';
			endfor;
		elseif (is_array($list)):
			foreach ($list as $k => $v):
				$html .= '<option value="' . $k . '" ' . ($k == $value ? 'selected' : '') . '>' . lang($v) . '</option>';
			endforeach;
		endif;
		$html .= '</select><label for="' . $id . '">' . lang($lang) . '：</label>';
		return $html;
	}
	public static function input_select(string $name, mixed $list, mixed $value = null, ?string $lang = null)
	{
		return self::input_required_select($name, $list, $value, $lang, '');
	}
	public static function input_langlist()
	{
		$list = scandir(MyApp::path('lang/'));
		$list = array_filter($list, fn($m) => !str_contains($m, '.'));
		return self::input_required_select('lang', array_combine(
			$list,
			array_map(
				fn($m) => 'lang_' . str_replace('-', '_', $m),
				$list
			)
		));
	}
	/**
	 * 删除板块
	 */
	public static function forum_delete(mixed $_forum, $size = 100): bool
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
		// hook model_forum_delete_start.php
		$id = 1;
		flush(); #兼容,一般可忽略
		if (empty($_forum)):
			echo 'event:close' . PHP_EOL; #相当于响应side事件
			echo 'id:' . $id . PHP_EOL; #相当于响应id
			echo 'data:{"message":"' . lang('forum_not_exists') . '","url":"' . MyApp::purl('forum/list') . '"}' . PHP_EOL; #相当于事件里的event.data
			echo PHP_EOL . PHP_EOL; #重点 每条消息末端必须用两个\r\n隔开
			exit;
		endif;
		if (MyDB::t('forum')->selectCount() == 1):
			#只有一个板块不允许删除
			echo 'event:close' . PHP_EOL; #相当于响应side事件
			echo 'id:' . $id . PHP_EOL; #相当于响应id
			echo 'data:{"message":"' . lang('forum_cant_delete_system_reserved') . '","url":"' . MyApp::purl('forum/list') . '"}' . PHP_EOL; #相当于事件里的event.data
			echo PHP_EOL . PHP_EOL; #重点 每条消息末端必须用两个\r\n隔开
			exit;
		endif;
		$_fid = $_forum['fid'];
		if (isset($_forum['fup'])):
			#不能删除子版块功能
			foreach ($GLOBALS['forumlist'] as $k => $v):
				if ($v['fup'] == $_fid):
					echo 'event:close' . PHP_EOL; #相当于响应side事件
					echo 'id:' . $id . PHP_EOL; #相当于响应id
					echo 'data:{"message":"' . lang('forum_please_delete_sub_forum') . '","url":"' . MyApp::purl('forum/list') . '"}' . PHP_EOL; #相当于事件里的event.data
					echo PHP_EOL . PHP_EOL; #重点 每条消息末端必须用两个\r\n隔开
					exit;
				endif;
			endforeach;
		endif;
		echo 'event:open' . PHP_EOL; #相当于响应side事件
		echo 'id:' . $id . PHP_EOL; #相当于响应id
		echo 'data:{"message":"' . lang('forum_event_stream_start') . '"}' . PHP_EOL; #相当于事件里的event.data
		echo PHP_EOL . PHP_EOL; #重点 每条消息末端必须用两个\r\n隔开
		flush(); #兼容,一般可忽略
		$id++;
		sleep(1);
		$threadlist = MyDB::t('thread')->where(['fid' => $_fid], MyDB::LIMIT($size), MyDB::MODE_ITERATOR, array('uid', 'tid', 'subject'));
		if ($threadlist->valid()):
			echo 'event:progress' . PHP_EOL; #相当于响应side事件
			echo 'id:' . $id . PHP_EOL; #相当于响应id
			echo 'data:{"message":"' . sprintf(lang('forum_event_stream_progress'), $size) . '"}' . PHP_EOL; #相当于事件里的event.data
			echo PHP_EOL . PHP_EOL; #重点 每条消息末端必须用两个\r\n隔开
			flush(); #兼容,一般可忽略
			$id++;
			sleep(1);
			#存在主题 先删除主题
			foreach ($threadlist as $thread):
				echo 'event:progress' . PHP_EOL; #相当于响应side事件
				echo 'id:' . $id . PHP_EOL; #相当于响应id
				echo 'data:{"message":"' . sprintf(lang('forum_event_stream_progress_subject'), $thread['subject']) . '"}' . PHP_EOL; #相当于事件里的event.data
				echo PHP_EOL . PHP_EOL; #重点 每条消息末端必须用两个\r\n隔开
				flush(); #兼容,一般可忽略
				$id++;
				thread_delete($thread['tid']);
			endforeach;
			echo 'event:progress' . PHP_EOL; #相当于响应side事件
			echo 'id:' . $id . PHP_EOL; #相当于响应id
			echo 'data:{"message":"' . sprintf(lang('forum_event_stream_progress_success'), $size) . '"}' . PHP_EOL; #相当于事件里的event.data
			echo PHP_EOL . PHP_EOL; #重点 每条消息末端必须用两个\r\n隔开
			flush(); #兼容,一般可忽略
			$id++;
			sleep(1);
			exit;
		endif;
		MyDB::t('forum')->delete_by_where(['fid' => $_fid]);
		MyDB::t('forum_access')->delete_by_where(['fid' => $_fid]);
		echo 'event:close' . PHP_EOL; #相当于响应side事件
		echo 'id:' . $id . PHP_EOL; #相当于响应id
		echo 'data:{"message":"' . lang('forum_event_stream_close') . '","url":"' . MyApp::purl('forum/list') . '"}' . PHP_EOL; #相当于事件里的event.data
		echo PHP_EOL . PHP_EOL; #重点 每条消息末端必须用两个\r\n隔开
		sleep(1);
		forum_list_cache_delete();
		// hook model_forum_delete_end.php
		exit;
	}
}
