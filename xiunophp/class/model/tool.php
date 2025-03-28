<?php

namespace model;

use MyApp;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use ScssPhp\ScssPhp\ValueConverter;

class tool
{

	// hook model_tool_start.php
	/**
	 * scss编译
	 * @param string $sitepath css文件保持目录
	 * @param string $sourcefile scss源文件
	 * @param string $sourceroot 索引目录
	 */
	public static function scss_write(string $sourcefile, string $sitepath = ''): bool
	{
		if (!class_exists('ScssPhp\ScssPhp\Compiler', false)):
			include(MyApp::convert_path('phar://' . XIUNOPHP_PATH . 'class/phar/ScssPhp.phar/autoload.php'));
		endif;
		// hook model_tool_scss_write.php
		if (empty($sitepath)):
			$sitepath = preg_replace('/\.scss$/is','.css',$sourcefile);
		endif;
		MyApp::create_dir(dirname($sitepath));
		$SCSS = new Compiler();
		$SCSS->setOutputStyle(OutputStyle::COMPRESSED);
		$SCSS->addImportPath(fn($filename) => self::scss_import($filename));
		$SCSS->registerFunction(
			'SitePath',
			fn($args) => self::scss_format(APP_SITE . (param($args)[0] ?? '')),
			['arg...']
		);
		/**
		 * 初始化scss变量
		 */
		$Variables = array_merge(MyApp::app()->datas['site'],array(
			'lg-size' => '992px',
			'md-size' => '768px',
			'sm-size' => '576px',
			'root' => APP_SITE,
			'imgroot' =>MyApp::app()->datas['site']['img'],
			'fontroot' => MyApp::app()->datas['site']['font']
		));
		foreach ($Variables as $k => $v):
			$Variables[$k] =  self::scss_format($v);
		endforeach;
		$SCSS->addVariables($Variables);
		$content = file_get_contents($sourcefile);
		$cssText = $SCSS->compileString($content)?->getCss();
		if (empty($cssText)):
			return false;
		else:
			file_put_contents($sitepath, $cssText);
		endif;
		unset($SCSS);
		return true;
	}
	/**
	 * 格式化scss变量
	 */
	public static function scss_format(mixed $v): mixed
	{
		return ValueConverter::fromPhp($v);
	}
	/**
	 * scss 导入文件处理
	 */
	public static function scss_import(string $scss): ?string
	{
		// hook model_tool_scss_import.php
		if (str_starts_with($scss, APP_PATH)):
			return $scss;
		else:
			$char = substr($scss, 0, 1);
			if (in_array($char, ['.', '/', '\\'])):
				$path = realpath(APP_PATH . $scss);
				if (is_file($path)):
					return $path;
				endif;
			endif;
			if (is_file(MyApp::app()->datas['path']['scss'].$scss)):
				return MyApp::app()->datas['path']['scss'].$scss;
			endif;
		endif;
		return null;
	}
	/**
	 * scss函数参数格式化为一维数组
	 */
	public static function param(array $args): array
	{
		$param = [];
		foreach ($args[0][2] ?: $args[0][3] as $index => $arg):
			if (is_object($arg) && method_exists($arg, 'getDimension')):
				$param[$index] = $arg->getDimension();
			elseif (is_array($arg)):
				if ($arg[0] == 'list'):
					$value = [];
					foreach ($arg[2] ?: $arg[3] as $key => $item):
						if (is_object($item) && method_exists($arg, 'getDimension')):
							$value[$key] = $item->getDimension();
						elseif (is_array($item)):
							if ($item[0] == 'string'):
								$value[$key] = $item[2][0];
							endif;
						endif;
					endforeach;
					$param[$index] = $value;
					continue;
				elseif ($arg[0] == 'string'):
					$param[$index] = $arg[2][0];
				else:
					$param[$index] = NULL;
				endif;
			endif;
		endforeach;
		return $param;
	}
	// hook model_tool_end.php
}
