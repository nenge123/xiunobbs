<?php

namespace script;

use MyApp;
use plugin;

/**
 * 插件类函数复用
 */
trait plugunbase
{
	/**
	 * 插件目录名
	 */
	public static string  $dir;
	/**
	 * 插件目录下的WEB地址
	 */
	public static function site(string $name): string
	{
		return plugin::site(self::$dir . '/' . $name);
	}
	/**
	 * 插件目录下物理地址
	 */
	public static function path(string $name): string
	{
		return plugin::path(self::$dir . '/' . $name);
	}
	/**
	 * 插件目录下的模板文件地址
	 */
	public static function tpl_file(string $name): string
	{
		return self::path('view/htm/' . $name);
	}
	/**
	 * 插件目录模板文件
	 */
	public static function tpl_link(string $name): string
	{
		return plugin::parseFile(self::tpl_file($name));
	}
	/**
	 * 插件目录下的js文件WEB地址
	 */
	public static function js(string $name)
	{
		return self::site('view/js/' . $name);
	}
	/**
	 * 插件目录下的css文件WEB地址
	 */
	public static function css(string $name)
	{
		return self::site('view/css/' . $name);
	}
	/**
	 * 生成css到插件目录 返回WEB地址
	 */
	public static function scss(string $name)
	{
		if (defined('DEBUG') && DEBUG > 0):
			#不开启DEBUG 永不转换
			$input = self::path('view/scss/' . $name . '.scss');
			$output = self::path('view/css/' . $name . '.css');
			MyApp::scss($input,$output);
		endif;
		return self::css($name . '.css');
	}
	/**
	 * 生成css到插件目录 返回样式HTML代码
	 */
	public static function scsslink(string $name)
	{
		return '<link rel="stylesheet" type="text/css" href="' . self::scss($name) . '" />';
	}
	public static self $_app;
	/**
	 * 实例化自身
	 */
	public static function app()
	{
		if(!isset(self::$_app)):
			self::$_app = new self();
		endif;
		return self::$_app;
	}
}
