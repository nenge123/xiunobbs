<?php

function cache_new($cacheconf)
{
	// 缓存初始化，这里并不会产生连接！在真正使用的时候才连接。
	// 这里采用最笨拙的方式而不采用 new $classname 的方式，有利于 opcode 缓存。
	if ($cacheconf && !empty($cacheconf['enable'])) {
		switch ($cacheconf['type']) {
			case 'mysql':
				include(XIUNOPHP_PATH.'class/cache_mysql.class.php');
				$cache = new cache_mysql($cacheconf['mysql']);
				break;
			default:
				include(XIUNOPHP_PATH.'class/cache_file.class.php');
				$cache = new cache_file($cacheconf);
			break;
		}
		return $cache;
	}
	return NULL;
}

function cache_get($k, $c = NULL)
{
	$cache = $_SERVER['cache'];
	$c = $c ? $c : $cache;
	if (!$c) return FALSE;

	strlen($k) > 32 and $k = md5($k);

	$k = $c->cachepre . $k;
	$r = $c->get($k);
	return $r ?: NULL;
}

function cache_set($k, $v, $life = 0, $c = NULL)
{
	$cache = $_SERVER['cache'];
	$c = $c ? $c : $cache;
	if (!$c) return FALSE;

	strlen($k) > 32 and $k = md5($k);

	$k = $c->cachepre . $k;
	$r = $c->set($k, $v, $life);
	return $r;
}

function cache_delete($k, $c = NULL)
{
	$cache = $_SERVER['cache'];
	$c = $c ? $c : $cache;
	if (!$c) return FALSE;

	strlen($k) > 32 and $k = md5($k);

	$k = $c->cachepre . $k;
	$r = $c->delete($k);
	return $r;
}

// 尽量避免调用此方法，不会清理保存在 kv 中的数据，逐条 cache_delete() 比较保险
function cache_truncate($c = NULL)
{
	$cache = $_SERVER['cache'];
	$c = $c ? $c : $cache;
	if (!$c) return FALSE;

	//$k = $c->cachepre.$k;
	$r = $c->truncate();
	return $r;
}
