<?php

namespace lib;

use MyApp;

/**
 * HTML代码格式化
 * @method string getText() 输出纯文本
 * @method string parse() 输出安全HTML
 */
class html
{
	/**
	 * 输出安全HTML代码
	 */
	static public function parse(string $html, bool $ishtml = true)
	{

		$charset = MyApp::data('charset', 'UTF-8');
		$doc = new \DOMDocument('1.0', $charset);
		$doc->preserveWhiteSpace = false;
		$doc->loadHTML('<!DOCTYPE html><html><head><meta charset="' . $charset . '"></head><body>' . $html . '</body></html>', LIBXML_ERR_NONE | LIBXML_NOERROR);
		self::remove_node_child($doc->getElementsByTagName('body')[0]);
		//self::remove_nodes_tag($doc->documentElement->childNodes);
		#把所有内联CSS集合在一起,通过JS处理成无害CSS
		$styletext = '';
		foreach ($doc->getElementsByTagName('style') as $node):
			$styletext .= $node->textContent;
			$node->parentNode->removeChild($node);
		endforeach;
		if (!empty($styletext)):
			#print_r($styletext);
			$styleElement = $doc->createElement('app-style', $styletext);
			$doc->getElementsByTagName('body')[0]->appendChild($styleElement);
		endif;
		/*
		foreach(iterator_to_array($doc->getElementsByTagName('script')) as $node):
			$scriptElement = $doc->createElement('app-script',trim($node->textContent));
			$doc->getElementsByTagName('body')[0]->appendChild($scriptElement);
			$node->parentNode->removeChild($node);
		endforeach;
		*/
		$result = substr($doc->saveHTML($doc->getElementsByTagName('body')[0]), 6, -7);
		return $result;
		$body = $doc->getElementsByTagName('body')[0];
		if ($ishtml) :
			$html = '';
			if (empty($body->childNodes->length)) return $html;
			foreach ($body->childNodes as $outnode) :
				$html .= $doc->saveHTML($outnode);
			endforeach;
			return $html;
		endif;
		return $body;
	}
	/**
	 * 删除危险HTML元素属性
	 */
	static public function remove_node_attribute(\DOMElement &$node)
	{
		if (isset($node->attributes) && $node->attributes->length > 0) :
			foreach ($node->attributes as $attribute) :
				$name = $attribute->name;
				$value = $attribute->value;
				#$value = htmlspecialchars_decode($value);
				#XSS 默认开启 过滤事件 onxxx 剔除id属性
				if (stripos($name, 'on') === 0 || in_array($name, ['id', 'name'])) :
					$node->removeAttribute($name);
					continue;
				endif;
				#XSS
				if (stripos($value, 'javascript') !== false || stripos($value, 'eval') !== false || stripos($value, 'function') !== false) :
					#javascript:....;
					#href="data:text/html;base64,js code...
					$node->removeAttribute($name);
					continue;
				endif;
				#if (stripos($value,'eval')!==false||stripos($value,'function')!==false):
				#eval function ()=>;
				#    $node->removeAttribute($name);
				#    continue;
				#endif;
				#if (in_array($name, array('src', 'href'))) :
				#    if(!preg_match('/^(http:)?[^;\(\)\:]+$/i',$value)):
				#        $attribute->value = '#';
				#    endif;
				#endif;
				#$attribute->value = $value;
				if ($name == 'style' && self::dom_paser_style_attribute($attribute)) :
					$node->removeAttribute($name);
				endif;
			endforeach;
		endif;
	}
	/**
	 * 过滤style css 中定位
	 */
	static public function dom_paser_style_attribute(\DOMAttr &$attribute)
	{
		if (!empty($attribute->value)) :
			$value = explode(';', strtolower($attribute->value));
			$output = [];
			foreach ($value as $value) :
				if (empty($value)) continue;
				list($prop, $data) = explode(':', trim($value));
				$prop = trim($prop);
				$data = trim($data);
				if (str_starts_with($prop, 'position')) :
					if ($data == 'fixed') :
						$data = 'relative';
					endif;
				endif;
				$output[] = $prop . ':' . $data;
			endforeach;
			if (!empty($output)) :
				$attribute->value = implode(';', $output);
				return false;
			endif;
		endif;
	}
	/**
	 * 遍历子元素进行删除
	 */
	static public function remove_node_child(\DOMElement &$node)
	{
		self::remove_node_attribute($node);
		if (isset($node->childNodes) && $node->childNodes instanceof \DOMNodeList) :
			self::remove_nodes_tag($node->childNodes);
		endif;
	}
	/**
	 * 删除框架 脚本标签
	 */
	static public function remove_nodes_tag(\DOMNodeList &$nodes)
	{
		if (empty($nodes->length)) return;
		#$tag = array('script', 'style', 'link', 'iframe');
		foreach ($nodes as $node) :
			$name = strtolower($node->nodeName);
			if ($node->nodeType === 1) :
				if (in_array($name, array('meta', 'head'))) :
					$node->parentNode->removeChild($node);
					continue;
				endif;
				#if (in_array($name, $tag)) :
				#过滤脚本 为被动式
				#if($name == 'script'):
				#$node->parentNode->removeChild($node);
				#continue;
				#endif;
				#过滤iframe 为被动式
				if (in_array($name, array('iframe', 'script'))) :
					#删除 iframe script
					$node->parentNode->removeChild($node);
					continue;
					#, 'style'
					if ($name == 'script') :
						if (!empty($node?->attributes->getNamedItem('src'))) :
							$node->parentNode->removeChild($node);
							continue;
						endif;
					endif;
					$newElm = $node->ownerDocument->createElement('app-' . $name);
					foreach ($node->attributes as $attr) :
						#$newElm->setAttribute($attr->nodeName,$attr->nodeValue);
						$newElm->setAttributeNode($attr);
					endforeach;
					$newElm->setAttributeNode(new \DOMAttr('hidden'));
					foreach ($node->childNodes as $child) :
						$newElm->appendChild($child);
					endforeach;
					$node->parentNode->replaceChild($newElm, $node);
					unset($node);
					self::remove_node_child($newElm);
					continue;
				endif;
				if ($node instanceof \DOMElement) :
					self::remove_node_child($node);
				endif;
			endif;
		endforeach;
	}
	/**
	 * 获取HTML中纯文本
	 */
	public static function getText(string $html): string
	{
		$charset = MyApp::data('charset', 'UTF-8');
		$doc = new \DOMDocument('1.0', $charset);
		$doc->preserveWhiteSpace = false;
		$doc->loadHTML('<!DOCTYPE html><html><head><meta charset="' . $charset . '"></head><body>' . $html . '</body></html>', LIBXML_ERR_NONE | LIBXML_NOERROR);
		return trim($doc->getElementsByTagName('body')[0]->textContent);
	}
	public static array $_ubbdata;
	/**
	 * UBB功能
	 * 先过滤危险HTML 再替换UBB
	 */
	public static function ubb(string $html):string
	{
		return self::ubb_parse(self::parse($html));
	}
	/**
	 * ubb替换处理
	 */
	public static function ubb_parse(string $html): string
	{
		if (!isset(self::$_ubbdata)):
			self::$_ubbdata = array();
			$path = MyApp::path('conf/ubb.conf.php');
			if (is_file($path)):
				self::$_ubbdata = include($path);
			endif;
		endif;
		if (!empty(self::$_ubbdata)):
			foreach (self::$_ubbdata as $k => $v):
				if ($v['type'] = 'text'):
					$html = preg_replace($v['input'], $v['output'], $html);
				elseif ($v['type'] == 'func'):
					if (is_string($v['output'])):
						$html = preg_replace_callback($v['input'], fn($m) => $v['output']($m), $html);
					elseif (is_array($v['output'])):
						$html = preg_replace_callback($v['input'], fn($m) => $v['output']($m), $html);
					endif;
				endif;
			endforeach;
		endif;
		return $html;
	}
}
