<?php

namespace Nenge;

class template
{
	public array $replacecode = array(
		'search' => array(),
		'replace' => array()
	);
	public array $regexp = array(
		'subtemplate' => "/(\<\!\-\-)?\{subtemplate\s+([a-z0-9_:\-\/\.]+)\}(\-\-\>)?/is",
		'var' => "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)",
		'const' => "([A-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)",
		'obj' => "/\$[a-z]\w+(->\w+)+/"
	);
	public array $hookData = array();
	public array $blocks = array();
	public string $path = '';
	public string $file = '';
	public function __construct($name, $path, $cachefile)
	{
		$myapp = APP::app();
		$file = $this->get_path_file($path, $name, $myapp->data['path']);
		if (!$file) {
			throw $name;
		}
		$this->path = $path;
		$this->file = $file;
		$this->parse(file_get_contents($file), $cachefile);
	}
	public function parse($template, $cachefile = '')
	{
		$myapp = APP::app();
		#语句
		$template = preg_replace_callback('/\<\?php\s(.+?)\s\?\>/is', fn ($m) => $this->fn_php($m[1]), $template);
		#引用子模版
		$template = preg_replace_callback($this->regexp['subtemplate'], fn ($m) => $this->fn_contents($m[2]), $template);

		#scss
		$template = preg_replace_callback("/\<\!\-\-\{scss\s+([\w\d\:\_\-]+\.scss)\}\-\-\>/is", fn ($m) => $this->fn_scss($m[1]), $template);

		#引入模板
		$template = preg_replace_callback("/\<\!\-\-\{template\s(.+?)\}\-\-\>/is", fn ($m) => $this->fn_template($m[1]), $template);


		#其他
		$template = preg_replace("/\/\*\{(.+?)\}\*\//s", "{\\1}", $template);
		$template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);

		#模板文字 语言
		$template = preg_replace_callback("/\{lang\s([^\}]+)\}/is", fn ($m) => $this->fn_lang(trim($m[1])), $template);

		#url
		$template = preg_replace_callback("/[\n\r\t\s]*\{url\s(.+?)\}[\n\r\t\s]*/is", fn ($m) => $this->fn_url($m[1]), $template);

		#头像
		$template = preg_replace_callback("/[\n\r\t]*\{avatar\s(.+?)\}[\n\r\t]*/i", fn ($m) => $this->fn_avatartags($m[1]), $template);
		#插入执行代码
		$template = preg_replace_callback("/[\n\r\t]*\{eval\}[\n\r\t]*(\<\!\-\-)*(.+?)(\-\-\>)*[\n\r\t]*\{\/eval\}[\n\r\t]*/is", fn ($m) => $this->fn_evaltags($m[2]), $template);

		#插入执行代码
		$template = preg_replace_callback("/[\n\r\t]*\{eval\s(.+?)\}[\n\r\t]*/is", fn ($m) => $this->fn_evaltags($m[1]), $template);

		#时间处理 date("Y-m-d H:i:s");
		$template = preg_replace("/[\n\r\t]*\{time\s(.+?)\}[\n\r\t]*/is", "<?=\$myapp->time_format(\\1)?>", $template);
		$template = preg_replace("/[\n\r\t]*\{timehuman\s(.+?)\}[\n\r\t]*/is", "<?=\$myapp->time_human(\\1)?>", $template);
		#换行符
		$template = str_replace("{LF}", "<?=PHP_EOF?>", $template);

		#变量
		#$template = preg_replace("/\{(\\\$[a-zA-Z0-9_\-\>\[\]\'\"\$\.\x7f-\xff]+)\s(or|\?\?)\s([a-zA-Z0-9\']+)\}/s", "{echo \\1 ?? \\3}", $template);

		#变量
		$template = preg_replace("/\{(\\\$[\w\_]+?[^\}\s;]+)\s\?\?\s(\\\$[^\}+]|['\"].+['\"])\}/is",'<?=\\1?:\\2?>', $template);
		$template = preg_replace("/\{(\\\$[\w\_]+[^\}\s;]+)\}/is",'<?=\\1?>', $template);

		#hook
		if (!empty($myapp->data['settings']['include_hook'])) {
			$template = preg_replace_callback("/\{hook\s+(.+?)\}/is", fn ($m) => $this->fn_hooktags($m[1]), $template);
		} else {
			$template = preg_replace("/\{hook\s(.+?)\}/is", '', $template);
		}
		#变量
		#$template = preg_replace_callback("/" . $this->regexp['var'] . "/s", fn ($m) => $this->addquote($m[1]), $template);

		#变量
		$template = preg_replace_callback("/\<\?\=\<\?\=(.+?)\?\>\?\>/s", fn ($m) => '<?='.trim($m[1]).'?>', $template);

		#css scss
		$template = preg_replace_callback("/\<link\s*[^>]*href=\"([\w\d\:\_\-]+\.scss)\"[^>]*\>/", fn ($m) => $this->fn_scss($m[1]), $template);

		#echo
		$template = preg_replace_callback("/\{echo\s(.+?)\}/is", fn ($m) => '<?php echo '.$m[1].';?>', $template);

		$template = preg_replace_callback("/\{echovar\s([^\s]+?)(\s[^\}]+)?\}/is", fn ($m) => '<?php echo empty(' . $m[1] . ') ?'.trim(isset($m[2])?$m[2]:'').':' . $m[1] . ';?>', $template);

		#if
		$template = preg_replace_callback("/[\n\r\t\s]*{if\s(.+?)\}[\n\r\t]*/is", fn ($m) => $this->fn_stags("<?php if(" . $m[1] . ") { ?>"), $template);

		$template = preg_replace_callback("/[\n\r\t\s]*{ifvar\s(.+?)\}[\n\r\t]*/is", fn ($m) => $this->fn_stags("<?php if(!empty(" . $m[1] . ")) { ?>"), $template);

		#elseif
		$template = preg_replace_callback("/[\n\r\t]*\{elseif\s(.+?)\}[\n\r\t]*/is", fn ($m) => $this->fn_stags("<?php } elseif(" . $m[1] . ") { ?>"), $template);
		#elseif
		$template = preg_replace_callback("/[\n\r\t]*\{elseifvar\s(.+?)\}[\n\r\t]*/is", fn ($m) => $this->fn_stags("<?php } elseif(!empty(" . $m[1] . ")) { ?>"), $template);

		#else
		$template = preg_replace("/[\n\r\t]*\{else\}[\n\r\t]*/is", "<?php } else { ?>", $template);

		#endif
		$template = preg_replace("/[\n\r\t]*\{\/if\}[\n\r\t\s]*/is", "<?php } ?>", $template);
		#for
		$template = preg_replace("/[\n\r\t]*\{for\((.+?)\)\}[\n\r\t]*/is", "<?php for(\\1){ ?>", $template);
		#endfor
		$template = preg_replace("/[\n\r\t]*\{\/for\}[\n\r\t\s]*/is", "<?php } ?>", $template);

		#for
		$template = preg_replace_callback("/[\n\r\t]*\{for\s(.+?)\}[\n\r\t]*/is", fn ($m) => '<?php for(' . $m[1] . '){ ?>', $template);

		#loop
		$template = preg_replace_callback("/[\n\r\t\s]*\{loop\s(\S+)\s+(\S+)\}[\n\r\t]*/is", fn ($m) => $this->fn_looptags($m[1], $m[2]), $template);

		#loop
		$template = preg_replace_callback("/[\n\r\t\s]*\{loop\s(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*/is", fn ($m) => $this->fn_looptags($m[1], $m[2], $m[3]), $template);
		$template = preg_replace("/[\n\r\t]*\{\/loop\}[\n\r\t\s]*/is", "<?php } ?>", $template);

		#常量
		$template = preg_replace_callback("/\{" . $this->regexp['const'] . "\}/s", fn ($m) => '<?=' . $this->fn_stags($m[1]) . '?>', $template);

		if (!empty($this->replacecode['search'])) {
			$template = str_replace($this->replacecode['search'], $this->replacecode['replace'], $template);
		}
		$template = preg_replace_callback("/\"(http)?[\w\.\/:]+\?[^\"]+?&[^\"]+?\"/", fn ($m) => $this->transamp($m[0]), $template);

		$template = preg_replace_callback("/\<script[^\>]*?src=\"(.+?)\"(.*?)\>\s*\<\/script\>/is", fn ($m) => $this->fn_stripscriptamp($m[1], $m[2]), $template);

		#$template = preg_replace("/\<\?\s+/is", "<?php ", $template);
		$template = preg_replace("/\<\?\=(.+?)\?\>/s", "<?php echo \\1;?>", $template);
		if (!empty($myapp->plugin['method']['template'])) {
			foreach ($myapp->plugin['method']['template'] as $k => $v) {
				$plugin_class = array($myapp->plugin_read_class($v), 'template');
				if (is_callable($plugin_class)) {
					$template = call_user_func($plugin_class, $template, $this->path, $this->file);
				}
			}
		}
		if (!empty($cachefile)) {
			$template = $this->str_head() . $template;
			$template = preg_replace("/\?><\?php\s/is", PHP_EOL, $template);
			file_put_contents($cachefile, $template);
		} else {
			return $template;
		}
	}
	public function str_head()
	{
		return '<?php defined("XIUNO")||die("back to <a href=\"/\">Home</a>");use Nenge\APP;use Nenge\DB;if(empty($myapp))$myapp = APP::app();if(empty($language))$language = Nenge\language::app();if(empty($sitelink))$sitelink = Nenge\sitelink::app();?>';
	}
	public function fn_lang($param1)
	{
		if (empty($param1)) return '';
		if (preg_match('/^[^\"\']+$/', $param1)) $param1 = '"' . $param1 . '"';
		return '<?=$language[' . $param1 . ']?>';
	}
	public function fn_var($param1)
	{
		return preg_replace("/(\\\$[a-zA-Z0-9_\>\[\]\'\"\$\.\x7f-\xff]+)/is", "{\\1}", $this->addquote(trim($param1)));
	}
	public function fn_url($param1)
	{
		$url_list = explode(' ', trim($param1));
		$url_name = trim(array_shift($url_list));
		$url_param = array_shift($url_list) ?: '""';
		$url_param = trim($url_param);
		if (preg_match('/^[^\"\']+$/', $url_name)) $url_name = '"' . $url_name . '"';
		if (preg_match('/^[^\"\']+$/', $url_param)) $url_param = '"' . $url_param . '"';
		$i = count($this->replacecode['search']);
		$this->replacecode['search'][$i] = $search = "<!--URL_TAG_$i-->";
		$this->replacecode['replace'][$i] = '<?=$myapp->url(' . $url_name . ',' . $url_param . ')?>';
		return $search;
	}
	public function fn_avatartags($parameter)
	{
		$parameter = stripslashes($parameter);
		$i = count($this->replacecode['search']);
		$this->replacecode['search'][$i] = $search = "<!--AVATAR_TAG_$i-->";
		$this->replacecode['replace'][$i] = "<?php echo \$myapp->avatar($parameter);?>";
		return $search;
	}
	public function fn_php($php)
	{
		$myapp = APP::app();
		$i = count($this->replacecode['search']);
		$this->replacecode['search'][$i] = $search = "<!--PHP_TAG_$i-->";
		$this->replacecode['replace'][$i] = '<?php '.PHP_EOL.$php.PHP_EOL.'?>';
		return $search;
	}
	public function fn_evaltags($php)
	{
		$myapp = APP::app();
		$i = count($this->replacecode['search']);
		$this->replacecode['search'][$i] = $search = "<!--EVAL_TAG_$i-->";
		$this->replacecode['replace'][$i] = !empty($myapp->conf['debug']) ? '<?php ' . preg_replace(array('/^L\d+[\w\.\/]*\-\-\>/', '/\<\!\-\-L\d+[\w\.\/]*\-\-\>/', '/\<\!\-\-L\d+[\w\.\/]*$/', '/^\s*\<\!\-\-/', '/\-\-\>\s*$/'), '', $php) . ';?>' : "<?php {$php};?>";
		return $search;
	}
	public function fn_hooktags($hook)
	{
		$myapp = APP::app();
		if (!isset($myapp->plugin['hook_data'])) {
			$myapp->write_plugin_data('hook');
		}
		if (!empty($myapp->plugin['hook_data'][$hook])) {
			$i = count($this->replacecode['search']);
			$this->replacecode['search'][$i] = $search = "<!--HOOK_TAG_$i-->";
			$this->replacecode['replace'][$i] = $myapp->plugin['hook_data'][$hook];
			return $search;
		}
		return '';
	}
	public function fn_template($file)
	{
		$i = count($this->replacecode['search']);
		$this->replacecode['search'][$i] = $search = "<!--INCLUDE_TAG_$i-->";
		if (preg_match('/^([a-z0-9\:\/]+)$/', $file)) $file = '"' . $file . '"';
		$this->replacecode['replace'][$i] = "<?php include \$myapp->template(" . $file . ");?>";
		return $search;
	}
	public function fn_scss($name, $link = false)
	{
		$myapp = APP::app();
		list($path, $cachefile, $csslink) = $myapp->str_path($name, 'css');
		if (!is_file($path)) {
			$path = $myapp->data['path']['css'] . basename($path);
		}
		if (is_file($path)) $this->SaveScssContent($path, $cachefile, $myapp->data['site']);
		if ($link) return $csslink;
		return '<link rel="stylesheet" type="text/css" href="' . $csslink . '?' . $myapp['ver'] . '" />';
	}
	public function SaveScssContent($srcFile, $tempFile, $var = array())
	{
		$SCSS = new \ScssPhp\ScssPhp\Compiler();
		$SCSS->setOutputStyle('compressed');
		$SCSS->setImportPaths(dirname($srcFile) . '\\');
		if (!empty($var)) {
			$SCSS->addVariables($var);
		}
		$scssStr = file_get_contents($srcFile);
		file_put_contents($tempFile, $SCSS->compileString($scssStr)->getCss());
	}
	public function fn_looptags($param1, $param2, $param3 = '')
	{
		if (preg_match("/^\<\?\=(\\\$.+?)\?\>$/s", $param1, $matches)) {
			$param1 = $matches[1];
		}
		if (preg_match('/\(.*\)$/', $param1) == 1) $return = '<?php ';
		else $return = '<?php if(!empty(' . $param1 . ') && is_array(' . $param1 . ')) ';
		if ($param3) {
			$return .= 'foreach(' . $param1 . ' as ' . $param2 . ' => ' . $param3 . ') { ?>';
		} else {
			$return .= 'foreach(' . $param1 . ' as ' . $param2 . ') { ?>';
		}
		return $this->fn_stags($return);
	}
	public function fn_stripscriptamp($s, $extra)
	{
		$s = str_replace('&amp;', '&', $s);
		return "<script src=\"$s\" type=\"text/javascript\"$extra></script>";
	}
	public function echopolyfill($str)
	{
		$str = str_replace(' or ', ' ?? ', $str);
		if (strpos($str, ' ?? ') !== false && version_compare(PHP_VERSION, '7.0', '<')) {
			$str = preg_replace('/^(.+)\s\?\?\s(.+)$/', " (\\1) ?: (\\2) ", $str);
		}
		return $str;
	}
	public function transamp($str)
	{
		$str = str_replace('&', '&amp;', $str);
		$str = str_replace('&amp;amp;', '&amp;', $str);
		return $str;
	}
	public function addquote($var)
	{
		return str_replace("\\\"", "\"", preg_replace_callback("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", fn ($m) => $this->fn_isnum($m[1]), $var));
	}
	public function fn_isnum($param1)
	{
		return is_numeric($param1) ? '[' . $param1 . ']' : "['" . $param1 . "']";
	}
	public function fn_stags($expr, $statement = '')
	{
		$expr = str_replace('\\\"', '\"', preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr));
		$statement = str_replace('\\\"', '\"', $statement);
		return $expr . $statement;
	}
	public function fn_contents($tfile, $limit = 0)
	{
		if ($limit > 4) return '';
		#防止无限套娃循环
		if (isset($this->hookData[$tfile])) return $this->hookData[$tfile];
		$myapp = APP::app();
		list($path, $tfile, $tlink, $file) = $myapp->str_path($tfile, 'template');
		if ($path == $myapp->data['path']['template']) {
			$path = $this->path;
		}
		$filepath = $this->get_path_file($path, $file, $myapp->data['path']);
		if (!$filepath) {
			return $this->hookData[$file] = '<!-- !lost template:' . $file . '! -->';
		}
		$template = preg_replace_callback($this->regexp['subtemplate'], fn ($subfile) => $this->fn_contents($subfile[2], $limit + 1), file_get_contents($filepath));
		$this->hookData[$file] = $template;
		return $this->hookData[$file];
	}
	public function get_path_file($path, $file, $base = false)
	{
		if (is_file($path . $file . '.htm')) {
			$filepath = $path . $file . '.htm';
		} else if (is_file($path . $file . '.php')) {
			$filepath = $path . $file . '.php';
		} else if ($base && isset($base['styletemplate']) && $path != $base['styletemplate'] && $path != $base['template']) {
			return $this->get_path_file($base['styletemplate'], $file, $base);
		} else if ($base && $path != $base['template'] && is_file($base['template'] . $file . '.html')) {
			$filepath = $base['template'] . $file . '.htm';
		} else {
			return false;
		}
		return $filepath;
	}
}
