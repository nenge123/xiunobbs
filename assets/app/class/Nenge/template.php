<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 模板转换与生成
 */
namespace Nenge;
class template
{
	public array $replacecode = array(
		'search' => array(),
		'replace' => array()
	);
	public int $index = 0;
	public array $hookData = array();
	public string $data;
	public function __construct($path, $cachefile)
	{
		$this->parse(file_get_contents($path), $cachefile);
	}
	public function parse($template, $cachefile = '')
	{
		$myapp = APP::app();
		#引用子模版
		$template = preg_replace_callback("/\<\!\-\-\{subtemplate\(\'(.+?)\'\)\}\-\-\>/", fn ($m) => $this->fn_contents($m[1]), $template);

		$template = preg_replace_callback("/\<\!\-\-\{template\((.+?)\)\}\-\-\>/", fn ($m) => '<?php include($myapp->template('.$m[1].',__DIR__.DIRECTORY_SEPARATOR)); ?>', $template);

		#语句
		$template = preg_replace_callback('/\<\?php\s(.+?)\s*\?\>/is', fn ($m) => $this->fn_php($m[1]), $template);

		$template = preg_replace_callback("/\<link\s*[^>]*?href=\"([\w\d\:\_\-]+\.scss)\"[^>]*\>/i", fn ($m) =>$this->fn_scss($m[1]), $template);
		
        
		#快速变量替换
		$template = preg_replace_callback("/\{\{\s(.+?)\s\}\}/",fn($m)=>$this->getVar($m[1]),$template);

		#其他
		#$template = preg_replace("/\/\*\{(.+?)\}\*\//s", "{\\1}", $template);
		$template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);
		
		#hook
		$template = preg_replace_callback('/\{hook\s*([^\}]+)\}/is', fn ($m) => $this->fn_hook_plugin($m[1]), $template);

		#模板文字 语言
		$template = preg_replace_callback("/\{lang\s([^\}]+)\}/", fn ($m) => '<?=$language[\''. addslashes($m[1]) .'\']?>', $template);

		#路径
		$template = preg_replace_callback("/\{site\s(.+?)\}/", fn ($m) => '<?=$myapp->data[\'site\']["'.trim($m[1]).'"]?>', $template);

		#url
		$template = preg_replace_callback("/\{url\s(.+?)\}/s", fn ($m) => $this->fn_url($m[1]), $template);

		#头像
		$template = preg_replace_callback("/\{avatar\s(.+?)\}/", fn ($m) => $this->fn_avatartags($m[1]), $template);

		#时间处理 date("Y-m-d H:i:s");
		$template = preg_replace("/\{time\s(.+?)\}/s", "<?=\$myapp->formatTime(\\1)?>", $template);
		$template = preg_replace("/\{timehuman\s(.+?)\}/s", "<?=\$myapp->humanTime(\\1)?>", $template);
		
		

		#变量
		$template = preg_replace("/\{(\\\$[\w\_]+?[^\}\s;]+?)\s\?\?\s(\\\$[^\}+]|['\"].+['\"])\}/is",'<?=\\1?:\\2?>', $template);
		$template = preg_replace("/\{(\\\$[\w\_]+[^\}\s;]*?)\}/is",'<?=\\1?>', $template);

		#变量
		$template = preg_replace_callback("/\<\?\=\<\?\=(.+?)\?\>\?\>/s", fn ($m) => '<?='.trim($m[1]).'?>', $template);


		#echo
		$template = preg_replace_callback("/\{echo\s(.+?)\}/s", fn ($m) => '<?='.trim($m[1]).'?>', $template);

		$template = preg_replace_callback("/\{echovar\s([^\s]+?)(\s[^\}]+)?\}/s", fn ($m) => '<?php echo empty(' . $m[1] . ') ?'.trim(isset($m[2])?$m[2]:'').':' . $m[1] . ';?>', $template);

		#if
		$template = preg_replace_callback("/{if\s(.+?)\}/s", fn ($m) => $this->fn_stags("<?php if(" . $m[1] . "): ?>"), $template);

		#elseif
		$template = preg_replace_callback("/\{elseif\s(.+?)\}/s", fn ($m) => $this->fn_stags("<?php elseif(" . $m[1] . "): ?>"), $template);
		#elseif

		#else
		$template = preg_replace("/\{else\}/s", "<?php else: ?>", $template);

		#endif
		$template = preg_replace("/\{\/if\}/s", "<?php endif; ?>", $template);

		#loop
		$template = preg_replace_callback("/\{loop\s(\S+)\s+(\S+)\}/s", fn ($m) => $this->fn_looptags($m[1], $m[2]), $template);

		#loop
		$template = preg_replace_callback("/\{loop\s(\S+)\s+(\S+)\s+(\S+)\}/s", fn ($m) => $this->fn_looptags($m[1], $m[2], $m[3]), $template);
		$template = preg_replace("/\{\/loop\}/s", "<?php endforeach; ?>", $template);

		foreach($this->replacecode['search'] as $index=>$search):
			$template = str_replace($search, $this->replacecode['replace'][$index], $template);
		endforeach;
		#$template = preg_replace_callback("/\"(http)?[\w\.\/:]+\?[^\"]+?&[^\"]+?\"/", fn ($m) => $this->transamp($m[0]), $template);

		#$template = preg_replace_callback("/\<script[^\>]*?src=\"(.+?)\"(.*?)\>\s*\<\/script\>/is", fn ($m) => $this->fn_stripscriptamp($m[1], $m[2]), $template);

		#$template = preg_replace("/\<\?\s+/is", "<?php ", $template);
		#换行符
		$template = str_replace("{LF}", "<?=PHP_EOF?>", $template);
		/*$template = preg_replace("/\<\?\=(.+?)\?\>/s", "<?php echo \\1;?>", $template);*/
		$template = $myapp->plugin_set('template',$template);
		if (!empty($cachefile)) {
			$template = "<?php \n/**\n * @author Nenge<m@nenge.net>\n * @copyright Nenge.net\n * @license GPL\n * @link https://nenge.net\n * 模板\n */\ndefined('WEBROOT') or die('back to <a href=\"/\">Home</a>');\nif(empty(\$myapp)):\n\t\$myapp = \Nenge\APP::app();\nendif;\n?>". $template;
			$template = preg_replace("/\s\?><\?php\s/is",'', $template);
			file_put_contents($cachefile, $template);
		} else {
			$this->data = $template;
		}
	}
	public function fn_var($param1)
	{
		return preg_replace("/(\\\$[a-zA-Z0-9_\>\[\]\'\"\$\.\x7f-\xff]+)/is", "{\\1}", $this->addquote(trim($param1)));
	}
	public function getVar($param1,$bool=false){
		$param1 = trim($param1);
		if(strpos($param1,'$')===0) return '<?='.trim($param1).'?>';
		if(strpos($param1,'\\')===0) return '<?='.trim(substr($param1,1)).'?>';
		$param = explode('.',$param1);
		$str = '$myapp';
		foreach($param as $k=>$v){
			if($k==0){
				if($v=='language'):
					$str = '$language';
					continue;
				endif;
				if(strpos($param1,'[')===0){
					$str.=$v;
					continue;
				}
				if(preg_match("/^[\w\_]+\(/",$v)){
					if(!method_exists(APP::app(),strstr($v, '(', true))){
						return '<?='.trim($param1).'?>';
					}
					$str .= '->'.$param1;
					break;
				}
				if(preg_match("/^[\w\_]+\[/",$v)){
					$str .= '->'.$param1;
					break;
				}
			}
			if(strpos($v,'$')===0||is_numeric($v)){
				$str .= '['.$v.']';
			}else{
				$str .= '[\''.$v.'\']';
			}
		}
		if($bool)return $str;
		return '<?='.trim($str).'?>';
	}
	public function getIfVar($param1,$param2){
		$param1 = trim($param1);
		$str = '<?php if';
		if(preg_match("/(?<=[^\-])[\=\!\>\<]+/",$param1)){
			$str.='('.$param1.')';
		}else if(strpos($param1,'!')===0){
			$str.='(empty('.ltrim($param1,'!').'))';
		}else{
			$str.='(!empty('.$param1.'))';
		}
		$param = explode('::',trim($param2));
		$paramArr = [];
		foreach($param as $v){
			$v = trim($v);
			if(strpos($v,'\'')!==0&&strpos($v,'"')!==0&&strpos($v,'$')!==0)$v = '\''.$v.'\'';
			$paramArr[] = ':echo '.$v.';';
		}
		return $str.implode('else',$paramArr).'endif;?>';

	}
	public function fn_url($param1)
	{
		$url_list = explode(' ', trim($param1));
		$url_name = trim(array_shift($url_list));
		$url_param = array_shift($url_list) ?: '""';
		$url_param = trim($url_param);
		if (preg_match('/^[^\"\']+$/', $url_name)) $url_name = '"' . $url_name . '"';
		if (preg_match('/^[^\"\']+$/', $url_param)) $url_param = '"' . $url_param . '"';
		$this->replacecode['search'][$this->index] = $search = '<!-- URL_TAG_'.$this->index.' -->';
		$this->replacecode['replace'][$this->index] = '<?=$myapp->url(' . $url_name . ',' . $url_param . ')?>';
		$this->index+=1;
		return $search;
	}
	public function fn_avatartags($parameter)
	{
		if (preg_match('/^[^\"\']+$/', $parameter)) $parameter = '"' . $parameter . '"';
		$this->replacecode['search'][$this->index] = $search = '<!-- AVATAR_TAG_'.$this->index.' -->';
		$this->replacecode['replace'][$this->index] = '<?=$myapp->avatar('.$parameter.')?>';
		$this->index+=1;
		return $search;
	}
	public function fn_php($php)
	{
		$this->replacecode['search'][$this->index] = $search = '<!-- PHP_TAG_'.$this->index.' -->';
		$this->replacecode['replace'][$this->index] = '<?php '.PHP_EOL.$php.PHP_EOL.'; ?>';
		$this->index+=1;
		return $search;
	}
	public function fn_evaltags($php)
	{
		$this->replacecode['search'][$this->index] = $search = '<!-- EVAL_TAG_'.$this->index.' -->';
		$this->replacecode['replace'][$this->index] = !defined('DEBUG') ? '<?php ' . preg_replace(array('/^L\d+[\w\.\/]*\-\-\>/', '/\<\!\-\-L\d+[\w\.\/]*\-\-\>/', '/\<\!\-\-L\d+[\w\.\/]*$/', '/^\s*\<\!\-\-/', '/\-\-\>\s*$/'), '', $php) . ';?>' : "<?php {$php};?>";
		$this->index+=1;
		return $search;
	}
	public function fn_hook_plugin($hook,$bool=false)
	{
		if(!preg_match("/^['\"\$]/",$hook)):
			$hook = '\''.$hook.'\'';
		endif;
		return '<?php $myapp->plugin_echo('.$hook.');?>';
	}
	public function fn_looptags($param1, $param2, $param3 = '')
	{
		if (preg_match("/^\<\?\=(\\\$.+?)\?\>$/s", $param1, $matches)) {
			$param1 = $matches[1];
		}
		if (!empty($param3)) {
			$return = '<?php foreach(' . $param1 . ' as ' . $param2 . ' => ' . $param3 . '): ?>';
		} else {
			$return = '<?php foreach(' . $param1 . ' as ' . $param2 . '): ?>';
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
		if ($limit > 5) return '<!-- template:'.$tfile.' -->';
		#防止无限套娃循环
		if (isset($this->hookData[$tfile])) return $this->hookData[$tfile];
		$this->hookData[$tfile] = '';
		$result = APP::app()->get_template_path($tfile);
		if(!empty($result)):
			if(is_string($result)):
				$this->hookData[$tfile] = '<?php include("'.$result.'"); ?>';
			else:
				$this->hookData[$tfile] = file_get_contents(APP::app()->get_dir_path($result[0]));
				$this->hookData[$tfile] = preg_replace_callback("/\<\!\-\-\{subtemplate\('(.+?)'\)\}\-\-\>/", fn ($subfile) => $this->fn_contents($subfile[1], $limit + 1),$this->hookData[$tfile]);
			endif;
		endif;
		return $this->hookData[$tfile];
	}
	public function fn_scss($param)
	{
		return APP::app()->scss($param);
	}
}
