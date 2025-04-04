<?php

/**
 * 插件处理综合函数
 */
class plugin
{
	static public array $regexp = array(
		#'subtemplate' => '/\<\!\-\-\{subtemplate\(\'(.+?)\'\)\}\-\-\>/',
		'scss' => '/\<link\s*[^>]*?href=\"([\w\d\:\_\-]+\.scss)\"[^>]*\>/im',
		'css' => '/\<link\s*[^>]*?href=\"css\/([\w\d\:\_\-]+)\.css\"\>/im',
		'template' => '/\<\!\-\-\{template\((.+?)\)\}\-\-\>/im',
		'foreach1' => '/\{each\s+(.+?)\s+(\$\w+?)\s+(\$\w+?)\s*\}/im',
		'foreach2' => '/\{each\s+(.+?)\s+(\$\w+?)\s*\}/im',
		'foreach3' => '/\{each\s+(.+?)\s+(\$\w+?)\s*=>\s*(\$\w+?)\}/im',
		'variable' => '/\{\{ (.+?) \}\}/m',
		#'modulefunc' => '/\{:(\w+[\w\_\d]+?)\((.+?)\)\}/',
	);
	/**
	 * 注入点解释函数
	 * _include
	 */
	public static function parseFile(string $srcfile, string $tmpfile = '')
	{
		// 合并插件，存入 tmp_path
		$srcfile = MyApp::convert_path($srcfile);
		$ext = '';
		if (empty($tmpfile)):
			if (str_starts_with($srcfile, 'phar://')):
				$isphar = true;
				$temppath = preg_replace('/^.+?[\\\\\/]([\-\w]+)\.phar/is', '\\1', $srcfile);
			else:
				$temppath = str_replace(APP_PATH, '', $srcfile);
			endif;
			$temppath = trim($temppath, ' .?#\/\\');
			$ext = pathinfo($temppath, PATHINFO_EXTENSION);
			if ($ext != 'php'):
				$temppath = substr($temppath, 0, strlen($temppath) - strlen($ext)) . 'php';
			endif;
			if (!empty($isphar)):
				$temppath = 'phar/' . $temppath;
			endif;
			$tmpfile = MyApp::tmp_path($temppath);

		/*
			$file_arr = array();
			$isphar = false;
			if (str_starts_with($srcfile, 'phar://')):
				$isphar = true;
				$temppath = preg_replace('/^.+?[\\\\\/]([\-\w]+)\.phar/is', '\\1', $srcfile);
			else:
				$len = strlen(APP_PATH);
				$temppath = substr($srcfile, $len);
			endif;
			$temppath = MyApp::convert_site($temppath);
			$pathinfo = pathinfo($temppath);
			$ext = $pathinfo['extension'];
			if (!empty($pathinfo['dirname']) && $pathinfo['dirname'] != '.'):
				$file_arr = explode('/', $pathinfo['dirname']);
			endif;
			#endif;
			if (empty($pathinfo['filename'])):
				xn_log($srcfile, 'error');
				echo $srcfile;
				exit;
			endif;
			$file_arr[] = $pathinfo['filename'] . '.php';
			if (in_array($file_arr[0], ['route', 'model', 'view'])):
				$tmpfile = MyApp::tmp_path($file_arr[0] . '/' . implode('_', array_slice($file_arr, 1)));
			elseif (in_array($file_arr[0],['admin','xiunophp'])):
				$tmpfile = MyApp::tmp_path(implode(DIRECTORY_SEPARATOR, $file_arr));
			elseif ($file_arr[0] == 'plugin' || $isphar):
				if ($isphar):
					$tmpfile = MyApp::tmp_path('plugin/phar/' . implode(DIRECTORY_SEPARATOR, $file_arr));
				else:
					$tmpfile = MyApp::tmp_path('plugin/' . $file_arr[1] . '/' . implode('_', array_slice($file_arr, 2)));
				endif;
			else:
				$tmpfile = MyApp::tmp_path(implode('_', $file_arr));
			endif;
			*/
		else:
			$ext = pathinfo($srcfile, PATHINFO_EXTENSION);
			if (!str_starts_with($tmpfile, APP_PATH)):
				$tmpfile =  MyApp::tmp_path($tmpfile);
			endif;
		endif;
		#DEBUG模式 即时编译 避免频繁的后台 更新缓存
		if (is_file($tmpfile)):
			if (!defined('DEBUG') || !DEBUG):
				#未开启DEBUG
				return $tmpfile;
			elseif (filemtime($srcfile) <= filemtime($tmpfile)):
				#判断修改时间
				return $tmpfile;
			endif;
		endif;
		// 开始编译
		$s = self::parseCompile($srcfile);
		// 支持 <template> <slot>
		/**
		 * 这个值是重置模板值???
		 */
		//$g_include_slot_kv = array();
		for ($i = 0; $i < 10; $i++):
			$s = preg_replace_callback('#<template\sinclude="(.*?)">(.*?)</template>#is', fn($m) => self::parseSlot($m), $s);
			if (!str_contains($s, '<template')):
				break;
			endif;
		endfor;
		#再一次解析hook
		$s = self::parseHook($s);
		if ($ext == 'htm'):
			#附加终止访问
			$s = '<?php !defined(\'APP_PATH\') AND exit(\'Access Denied.\');' . PHP_EOL . 'use model\tpl;' . PHP_EOL . ' ?>' . $s;
			#模板语法糖
			$s = self::parseVar($s);
		else:

		endif;
		self::parseWrite($tmpfile, trim($s));
		return $tmpfile;
	}
	/**
	 * 输出编译JS 返回一个网络URL
	 */
	public static function parseJS(string $srcfile, ?string $name = null)
	{
		$path = 'js/hook/' . ($name ?? pathinfo($srcfile, PATHINFO_FILENAME)) . '.js';
		$tmpfile = MyApp::view_path($path);
		self::parseFile($srcfile, $tmpfile);
		return MyApp::view_site($path);
	}
	/**
	 * 读取准备注入文件的内容
	 */
	public static function parseCompile($srcfile)
	{
		// 判断是否开启插件
		if (!empty(MyApp::conf('disabled_plugin'))):
			$s = file_get_contents($srcfile);
			return $s;
		endif;
		// 如果有 overwrite，则用 overwrite 替换掉
		$srcfile = self::overwrite($srcfile);
		$s = file_get_contents($srcfile);
		return self::parseHook($s);
	}
	/**
	 * 只返回一个权重最高的文件名
	 */
	public static function overwrite($srcfile)
	{
		$len = strlen(APP_PATH);
		$returnfile = $srcfile;
		$maxrank = 0;
		// 文件路径后半部分
		$filepath_half = substr($srcfile, $len);
		foreach (self::read_plugin_enabled() as $dir => $pconf) {
			$overwrite_file = self::path($dir) . '/overwrite/' . $filepath_half;
			if (is_file($overwrite_file)) {
				$rank = isset($pconf['overwrites_rank'][$filepath_half]) ? $pconf['overwrites_rank'][$filepath_half] : 0;
				if ($rank >= $maxrank) {
					$returnfile = $overwrite_file;
					$maxrank = $rank;
				}
			}
		}
		return $returnfile;
	}
	/**
	 * 转换HOOK注入
	 */
	public static function parseHook(string $template): string
	{
		// 最多支持 10 层
		for ($i = 0; $i < 10; $i++) {
			if (str_contains($template, '<!--{hook ') || str_contains($template, '// hook ')) {
				$template = preg_replace('#<!--{hook\s+(.*?)}-->#', '// hook \\1', $template);
				$template = preg_replace_callback(
					'#//\s*hook\s+(\S+)#is',
					fn($m) => self::read_hook_content($m),
					$template
				);
			} else {
				break;
			}
		}
		return $template;
	}
	/**
	 * 模板语法糖
	 */
	public static function parseVar(string $template): string
	{

		#内引用模板 必须完整结构
		$template = preg_replace_callback(
			self::$regexp['template'],
			fn($m) => '<?php include(plugin::parseFile(' . $m[1] . ')); ?>',
			$template
		);
		#语句 替换到省略模式
		$template = preg_replace(
			'/\<\!\-\-\{(.+?)\}\-\-\>/s',
			"{\\1}",
			$template
		);
		#快速变量替换 {{ xx }}
		$template = preg_replace_callback(
			self::$regexp['variable'],
			fn($m) => self::parse_fn_variable($m[1]),
			$template
		);
		#模板文字 语言
		$template = preg_replace_callback('/\{lang ([^\}]+)\}/', fn($m) => MyApp::Lang(trim($m[1])), $template);
		#echo
		$template = preg_replace_callback(
			'/\{echo (.+?)\}/m',
			fn($m) => '<?=' . trim($m[1]) . '??\'\'?>',
			$template
		);
		#eval
		$template = preg_replace_callback(
			'/\{eval ([^\}]+)\}/m',
			fn($m) => '<?php ' . trim($m[1]) . '; ?>',
			$template
		);
		#if 条件
		$template = preg_replace_callback(
			'/{if\s([^\}]+)\}/m',
			fn($m) => '<?php if(' . trim($m[1]) . '): ?>',
			$template
		);
		$template = preg_replace_callback(
			'/{if\(([^\}]+)\)\}/m',
			fn($m) => '<?php if(' . trim($m[1]) . '): ?>',
			$template
		);
		#elseif 条件转折
		$template = preg_replace_callback(
			'/\{elseif\s(.+?)\}/m',
			fn($m) => '<?php elseif(' . trim($m[1]) . '): ?>',
			$template
		);
		$template = preg_replace_callback(
			'/\{elseif\((.+?)\)\}/m',
			fn($m) => '<?php elseif(' . trim($m[1]) . '): ?>',
			$template
		);
		#else 条件否
		$template = preg_replace(
			'/\{\/?else\}/',
			'<?php else: ?>',
			$template
		);
		#endif 结束IF
		$template = preg_replace(
			'/\{\/if\}/',
			'<?php endif; ?>',
			$template
		);
		#endfor 结束for
		$template = preg_replace(
			'/\{\/for\}/',
			'<?php endfor; ?>',
			$template
		);
		#for
		$template = preg_replace_callback(
			'/\{for\((.+?)\)\}/m',
			fn($m) => '<?php for(' . trim($m[1]) . '): ?>',
			$template
		);
		#each foreach 循环
		$template = preg_replace_callback(
			self::$regexp['foreach1'],
			fn($m) => self::parse_fn_each($m),
			$template
		);
		#each foreach 循环
		$template = preg_replace_callback(
			self::$regexp['foreach2'],
			fn($m) => self::parse_fn_each($m),
			$template
		);
		#each foreach 循环
		$template = preg_replace_callback(
			self::$regexp['foreach3'],
			fn($m) => self::parse_fn_each($m),
			$template
		);
		#end foreach 结束循环
		$template = preg_replace(
			'/\{\/each\}/',
			'<?php endforeach; ?>',
			$template
		);
		$template = preg_replace('/[\n\r\s\t]+\?\>[\n\r\s\t]*\<\?php\s+/is', PHP_EOL, $template);
		return $template;
	}
	static public function parse_fn_each($param): string
	{
		if (!empty($param[3])) {
			$return = '<?php foreach(' . $param[1] . ' as ' . $param[2] . ' => ' . $param[3] . '): ?>';
		} else {
			$return = '<?php foreach(' . $param[1] . ' as ' . $param[2] . '): ?>';
		}
		return $return;
	}
	static public function parse_fn_variable($param1): string
	{
		$param1 = trim($param1);
		if (!empty($param1)):
			switch ($param1[0]):
				case '$':
				case '\\':
					return '<?=' . $param1 . '?>';
					break;
				case '\'':
				case '"':
					return '{{ ' . trim($param1, '\'" ') . ' }}';
					break;
				case ':':
					$param1 = substr($param1, 1);
					if (ctype_alnum($param1)):
						return '<?=MyApp::data(\'' . $param1 . '\')?>';
					endif;
					return '{{ ' . trim($param1, '.: ') . ' }}';
					break;
				default:
					#纯数字或字母
					if (ctype_alnum($param1)):
						return '<?=MyApp::value(\'' . $param1 . '\')?>';
					elseif (str_starts_with($param1, 'get_')):
						return '<?=MyApp::param(\'' . substr($param1, 4) . '\')?>';
					elseif (str_starts_with($param1, 'post_')):
						return '<?=MyApp::param(\'' . substr($param1, 5) . '\')?>';
					elseif (str_contains($param1, '::') || str_contains($param1, '->')):
						return '<?=' . $param1 . '?>';
					elseif (str_ends_with($param1, ')')):
						return '<?=MyApp::app()->' . $param1 . '??\'\'?>';
					endif;
					return '<?=' . $param1 . '?>';
					break;
			endswitch;
		endif;
		if (defined('DEBUG') && DEBUG):
			return '<!-- ' . $param1 . ' -->';
		endif;
		return '';
	}
	/**
	 * 套娃内容列表
	 *
	 * @var array
	 */
	public static array $slotList = array();
	/**
	 * 解析模板套娃
	 */
	public static function parseSlot($m)
	{
		$r = file_get_contents($m[1]);
		preg_match_all('#<slot\sname="(.*?)">(.*?)</slot>#is', $m[2], $m2);
		if (!empty($m2[1])) {
			$kv = array_combine($m2[1], $m2[2]);
			#也不知道会不会重叠 如有问题应该改为 self::$slotList = array_merge(self::$slotList,$kv);
			self::$slotList += $kv;
			foreach (self::$slotList as $slot => $content) {
				$r = preg_replace('#<slot\sname="' . $slot . '"\s*/>#is', $content, $r);
			}
		}
		return $r;
	}
	/**
	 * 把注入文件写入tmp
	 */
	public static function parseWrite($file, $s, $times = 3)
	{

		if (!is_dir(dirname($file))):
			mkdir(dirname($file), 0755, true);
		endif;
		while ($times-- > 0) {
			$fp = fopen($file, 'wb');
			if ($fp and flock($fp, LOCK_EX)) {
				$n = fwrite($fp, $s);
				fclose($fp);
				clearstatcache();
				return $n;
			} else {
				sleep(1);
			}
		}
		return FALSE;
	}
	public static array $pluginlist;
	/**
	 * 所有插件信息
	 */
	public static function read_plugin_data(): array
	{
		if (!isset(self::$pluginlist)):
			if(defined('PLUGIN_DIR')):
				self::$pluginlist = self::getItem('plugin_data');
			endif;
			if(empty(self::$pluginlist)):
				self::$pluginlist = array();
				$paths = glob(self::path() . '*/conf.json', GLOB_NOSORT);
				foreach ($paths as $file):
					$data = self::read_plugin_json($file);
					if (!empty($data)):
						self::$pluginlist[basename(dirname($file))] = $data;
					endif;
				endforeach;
				if (!empty(self::$pluginlist)):
					self::setItem('plugin_data', self::$pluginlist);
				endif;
			endif;
		endif;
		return self::$pluginlist;
	}
	/**
	 * 返回插件JSON信息
	 */
	public static function read_plugin_json(string $file): array
	{

		if (is_file($file)):
			$data = file_get_contents($file);
			return xn_json_decode($data);
		endif;
		return array();
	}
	public static function read_plugin_enabled()
	{
		$plugininfo = self::read_plugin_data() ?? array();
		return array_filter($plugininfo, fn($m) => !empty($m['enable'])&&!empty($m['installed']));
	}
	/**
	 * hook文件列表
	 *
	 * @var array
	 */
	public static array $hookslist;
	/**
	 * 读取插件hook文件列表
	 */
	public static function read_plugin_hook()
	{
		if (!isset(self::$hookslist)):
			self::$hookslist = array();
			$plugininfo = self::read_plugin_enabled();
			foreach ($plugininfo as $dir => $pconf):
				$hookpaths = self::glob_hook($dir);
				if (is_array($hookpaths)):
					foreach ($hookpaths as $hookpath):
						$hookname = file_name($hookpath);
						$rank = isset($pconf['hooks_rank']["$hookname"]) ? $pconf['hooks_rank']["$hookname"] : 0;
						self::$hookslist[$hookname][] = array('hookpath' => $hookpath, 'rank' => $rank);
					endforeach;
				endif;
			endforeach;
			foreach (self::$hookslist as $hookname => $arrlist):
				$arrlist = arrlist_multisort($arrlist, 'rank', FALSE);
				self::$hookslist[$hookname] = arrlist_values($arrlist, 'hookpath');
			endforeach;
		endif;
		return self::$hookslist;
	}
	/**
	 * 匹配所有hook文件
	 */
	public static function glob_hook($dir)
	{
		return glob(self::path($dir) . '/hook/*.*');
	}
	public static function path($dir = '')
	{
		return APP_PATH . 'plugin/' . $dir;
	}
	public static function site($dir = '')
	{
		return APP_SITE . 'plugin/' . $dir;
	}
	public static function get_plugin_json($dir)
	{
		return self::read_plugin_json(self::path($dir) . '/conf.json');
	}
	/**
	 * 读取hook文件内容
	 */
	public static function read_hook_content($m)
	{
		$hookfiles = self::read_plugin_hook();
		$s = '';
		$hookname = $m[1];
		if (!empty($hookfiles[$hookname])):
			$fileext = pathinfo($hookname, PATHINFO_EXTENSION);
			foreach ($hookfiles[$hookname] as $path):
				$t = file_get_contents($path);
				if ($fileext == 'php' && preg_match('#^\s*<\?php\s+exit;#is', $t)):
					// 正则表达式去除兼容性比较好。
					$t = preg_replace('#^\s*<\?php\s*exit;(.*?)(?:\?>)?\s*$#is', '\\1', $t);

				/* 去掉首尾标签
				if(substr($t, 0, 5) == '<?php' && substr($t, -2, 2) == '?>') {
					$t = substr($t, 5, -2);		
				}
				// 去掉 exit;
				$t = preg_replace('#\s*exit;\s*#', "\r\n", $t);
				*/
				endif;
				$s .= $t;
			endforeach;
		endif;
		return $s;
	}
	public static function json(array $data)
	{
		return json_encode($data, JSON_THROW_ON_ERROR | JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	}
	public static function  siteid()
	{
		$auth_key = MyApp::conf('auth_key');
		$siteip = $_SERVER['SERVER_ADDR'];
		$siteid = md5($auth_key . $siteip);
		return $siteid;
	}
	// 删除锁
	public static function unlock($lockname = '')
	{
		$lockfile = MyApp::tmp_path('lock_' . $lockname . '.lock');
		if (is_file($lockfile)):
			@unlink($lockfile);
		endif;
	}
	// 上锁
	public static function lock($lockname = '', $life = 10)
	{
		$lockfile = MyApp::tmp_path('lock_' . $lockname . '.lock');
		if (is_file($lockfile)) {
			// 大于 $life 秒，删除锁
			if ($_SERVER['REQUEST_TIME'] - filemtime($lockfile) > $life) {
				xn_unlink($lockfile);
			} else {
				// 锁存在，上锁失败。
				return FALSE;
			}
		}
		return file_put_contents($lockfile, $_SERVER['REQUEST_TIME'], LOCK_EX);
	}
	/**
	 * 写入文件数据缓存
	 */
	public static function setItem(string $name, mixed $data)
	{
		$filepath = MyApp::tmp_path('data/' . $name . '.php');
		if (is_array($data)):
			$data = '<?php' . PHP_EOL . 'return ' . var_export($data, true) . ';';
		endif;
		MyApp::create_dir(dirname($filepath));
		file_put_contents($filepath, $data);
	}
	/**
	 * 返回文件数据缓存
	 */
	public static function getItem(string $name)
	{
		$filepath = MyApp::tmp_path('data/' . $name . '.php');
		if (is_file($filepath)):
			return include($filepath);
		endif;
		return array();
	}
	/**
	 * 删除文件数据缓存
	 */
	public static function removeItem(string $name)
	{
		$filepath = MyApp::tmp_path('data/' . $name . '.php');
		if (is_file($filepath)):
			@unlink($filepath);
		endif;
	}
	/**
	 * 清空数据缓存
	 */
	public static function clearItem()
	{
		$path = MyApp::tmp_path('data/');
		if (is_dir($path)):
			foreach (scandir($path) as $file):
				if (str_ends_with($file, '.php')):
					@unlink($path . $file);
				endif;
			endforeach;
		endif;
	}
	/**
	 * 返回插件模板地址
	 */
	public static function tpl_file(string $file,?string $dir=null):string
	{
		if(empty($dir)):
			if(defined('PLUGIN_DIR')):
			$dir = PLUGIN_DIR;
			else:
				throw new \Exception('未知插件目录');
				return '';
			endif;
		endif;
		return self::path($dir.'/htm/'.$file);
	}
	/**
	 * 返回解析后的插件模板地址
	 */
	public static function tpl_link(string $file,?string $dir=null,?string $tmpfile):string
	{
		return self::parseFile(self::tpl_file($file,$dir),$tmpfile);
	}
}
