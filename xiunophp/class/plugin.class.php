<?php

/**
 * 插件处理综合函数
 */
class plugin
{
	/**
	 * 注入点解释函数
	 * _include
	 */
	public static function parseFile(string $srcfile)
	{
		// 合并插件，存入 tmp_path
		$len = strlen(APP_PATH);
		$filepath =  trim(substr($srcfile, $len), '. \/\\');
		$file_arr = explode('/', $filepath);
		if (in_array($file_arr[0], ['route', 'model', 'view', 'admin'])):
			$tmpfile = MyApp::tmp_path($file_arr[0] . '/' . implode('_', array_slice($file_arr, 1)));
		elseif ($file_arr[0] == 'plugin'):
			$tmpfile = MyApp::tmp_path($file_arr[0] . '/' . $file_arr[1] . '/' . implode('_', array_slice($file_arr, 2)));
		else:
			$tmpfile = MyApp::tmp_path(implode('_', $file_arr));
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
		for ($i = 0; $i < 10; $i++) {
			$s = preg_replace_callback(
				'#<template\sinclude="(.*?)">(.*?)</template>#is',
				fn($m) => self::parseSlot($m),
				$s
			);
			if (strpos($s, '<template') === FALSE) break;
		}
		#再一次解析hook
		$s = self::parseHook($s);
		self::parseWrite($tmpfile, $s);
		return $tmpfile;
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
			$overwrite_file = self::path($dir).'/overwrite/'.$filepath_half;
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
			self::$pluginlist = array();
			$paths = glob(self::path().'*/conf.json', GLOB_NOSORT);
			foreach ($paths as $file):
				$data = self::read_plugin_json($file);
				if (!empty($data)):
					self::$pluginlist[basename(dirname($file))] = $data;
				endif;
			endforeach;
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
		return array_filter($plugininfo, fn($m) => !empty($m['enable']));
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
	public static function path($dir='')
	{
		return APP_PATH . 'plugin/' . $dir;
	}
	public static function site($dir='')
	{
		return APP_SITE.'plugin/' . $dir;
	}
	public static function get_plugin_json($dir)
	{
		return self::read_plugin_json(self::path($dir). '/conf.json');
	}
	/**
	 * 读取hook文件内容
	 */
	public static function read_hook_content($m)
	{
		$hookfiles = self::read_plugin_hook();
		$s = '';
		$hookname = $m[1];
		if (!empty(self::$hooks[$hookname])):
			$fileext = pathinfo($hookname, PATHINFO_EXTENSION);
			foreach ($hookfiles as $path):
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
		return json_encode($data,JSON_THROW_ON_ERROR | JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
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
}
