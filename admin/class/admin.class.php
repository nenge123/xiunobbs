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

	public static function input_format_id($id)
	{
		return preg_replace('/[\[\]]/','-',$id);
	}
	public static function input_format_lang($name,$lang=null)
	{
		if(empty($lang)):
			return $lang===null?lang($name):'';
		endif;
		return lang($lang);
	}
	public static function input_check(string $name, mixed $value = null, ?string $lang = null)
	{
		if (!isset($value)):
			$value = MyApp::conf($name);
		endif;
		$id = self::input_format_id('check-' . $name . '-' . $_SERVER['REQUEST_TIME']);
		return '<label class="form-check-label" for="' . $id . '">' . self::input_format_lang($name ,$lang) . '</label><input class="form-check-input" type="checkbox" role="switch" id="' . $id . '" name="'.$name.'" value="1" ' . (empty($value) ? '' : 'checked') . '>';
	}
	public static function input_required_text(string $name, mixed $value = null, ?string $lang = null, $required = 'required')
	{
		if (!isset($value)):
			$value = MyApp::conf($name);
		endif;
		$id = self::input_format_id('text-' . $name . '-' . $_SERVER['REQUEST_TIME']);
		return '<input type="text" class="form-control" id="' . $id . '" name="' . $name . '" value="' . $value . '" ' . $required . '><label for="' . $id . '">' . self::input_format_lang($name ,$lang) . '：</label>';
	}
	public static function input_required_number(string $name, mixed $value = null, ?string $lang = null, $required = 'required')
	{
		if (!isset($value)):
			$value = MyApp::conf($name,0);
		endif;
		$id = self::input_format_id('text-' . $name . '-' . $_SERVER['REQUEST_TIME']);
		return '<input type="number" class="form-control" id="' . $id . '" name="' . $name . '" value="' . $value . '" ' . $required . '><label for="' . $id . '">' . self::input_format_lang($name ,$lang) . '：</label>';
	}
	public static function input_required_textarea(string $name, mixed $value = null, ?string $lang = null, $required = 'required')
	{
		if (!isset($value)):
			$value = MyApp::conf($name);
		endif;
		$id = self::input_format_id('textarea-' . $name . '-' . $_SERVER['REQUEST_TIME']);
		return '<textarea type="text" class="form-control" id="' . $id . '" name="' . $name . '" style="min-height:150px;" ' . $required . '>' . htmlentities($value) . '</textarea><label for="' . $id . '">' . self::input_format_lang($name ,$lang) . '：</label>';
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
		$html = '<select class="form-select" name="'.$name.'" id="' . $id . '" aria-label="' . $lang . '" ' . $required . '>';
		if (is_int($list)):
			$value = intval($value);
			for ($i = 0; $i <= $list; $i++):
				$html .= '<option value="' . $i . '" ' . ($i == $value ? 'selected' : '') . '>' . lang($lang . '_' . $i) . '</option>';
			endfor;
		elseif (is_array($list)):
			foreach ($list as $k => $v):
				$html .= '<option value="' . $k . '" ' . ($k == $value ? 'selected' : '').'>' . lang($v) . '</option>';
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
		$list = array_filter($list,fn($m)=>!str_contains($m,'.'));
		return self::input_required_select('lang',array_combine(
			$list,
			array_map(
				fn($m)=>'lang_'.str_replace('-','_',$m),$list
			)
		));
	}
	/**
	 * 删除板块
	 */
	public static function forum_delete(int $_fid):bool
	{
		
		#先删除主题 不知道10万以上数据会不会崩 求告知!!!

		#不管如何先开打开事务
		set_time_limit(0);
		MyDB::wlink()->commitStart();
		$list = MyDB::t('thread')->where(['fid'=>$_fid],MyDB::ORDER(['tid'=>'asc']),10,array('tid','uid'));
		$userupdate = [];
		foreach($list as $v):
			if(!isset($userupdate['uid']['-threads'])):
				$userupdate[$v['uid']]['-threads'] = 0;
			endif;
			$userupdate[$v['uid']]['-threads'] +=1;
			foreach(MyDB::t('post')->where(['tid'=>$v['tid']],MyDB::ORDER(['tid'=>'asc']),10,array('uid')) as $x):
				if(!isset($userupdate[$x['uid']]['-posts'])):
					$userupdate[$x['uid']]['-posts'] = 0;
				endif;
				$userupdate[$x['uid']]['-posts'] +=1;
			endforeach;
			MyDB::t('post')->delete_by_where(['tid'=>$v['tid']]);
		endforeach;
		foreach($userupdate as $u=>$d):
			MyDB::t('user')->update_by_where($d,['uid'=>$u]);
		endforeach;
		MyDB::t('thread')->delete_by_where(['fid'=>$_fid]);
		MyDB::t('forum')->delete_by_where(['fid'=>$_fid]);
		MyDB::t('forum_access')->delete_by_where(['fid'=>$_fid]);
		MyDB::wlink()->commitEnd();
		return true;
	}
}
