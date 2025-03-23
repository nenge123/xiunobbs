<?php
!defined('APP_PATH') and exit('Forbidden');
use \xn_search\search;
include(APP_PATH . 'plugin/xn_search_min/class/search.class.php');
$keyword = param('keyword');
//if(function_exists('iconv')): 
//繁体简体化
//$keyword = iconv('UTF-8', 'zh-cn.UTF-8', $keyword);	
//endif;
empty($keyword) and $keyword = param(1);
$keyword = trim($keyword);
$range = param(2, 1);
$page = param(3, 1);
$keyword_decode = xn_urldecode($keyword);
//浅唱修改开始
if ($keyword_decode <> ""):
	$ret = search::log($uid, $range, $keyword_decode);
	switch ($ret):
		case 1: //执行搜索
			break;
		case 2: //强制要求登录
			message(-1, jump('请登录后再次搜索', url('user-login')));
			exit;
		case 3: //搜索间隔少于10秒
			message(-1, jump('您在 10 秒内只能进行一次搜索', url('search--' . $range) . '?word=' . $keyword_decode));
			exit;
		case 4: //搜索间隔少于30秒
			message(-1, jump('您在 30 秒内只能进行一次搜索', url('search--' . $range) . '?word=' . $keyword_decode));
			exit;
		case 5: //搜索间隔少于60秒
			message(-1, jump('您在 60 秒内只能进行一次搜索', url('search--' . $range) . '?word=' . $keyword_decode));
			exit;
		case 6: //当天禁止该用户搜索功能
			message(-1, '搜索次数已达上限');
			exit;
		default: //发生未知错误
			message(-1, jump('发生未知错误', url('search--' . $range)));
			exit;
	endswitch;
endif;
//浅唱修改结束

$keyword_arr = array_filter(explode(' ', $keyword_decode), fn($m) => !empty($m));
$threadlist = $postlist = array();
$pagination = '';
$active = '';


$search_conf = search::conf();
$search_type = $search_conf['type'] ?? 'like';
$search_range = $search_conf['range'] ?? 0;

//$search_type = 'fulltext';

$pagesize = 1;
if (empty($page)):
	$page = 1;
else:
	$page = intval($page);
endif;
/*
全文搜索对中文不友好
$mysql_version = MyDB::version();
$keyword_decode_against = search_cn_encode($keyword_decode);
$keyword_decode_against = '+' . str_replace(' ', ' +', $keyword_decode_against);
$ft_info = array_column(MyDB::execute('SHOW VARIABLES LIKE "ft%";',2),1,0);
*/
if ($range == 1):
	$where = array('%subject' =>$keyword_arr);
	$total = MyDB::t('thread')->whereCount($where);
	$pagination = pagination(url('search-' . $keyword_decode . '-' . $range . '-{page}'), $total, $page, $pagesize);
	$datalist = MyDB::t('thread')->where($where,MyDB::ORDER(['tid' => 'desc']) . MyDB::LIMIT($page, $pagesize),10);
	$threadlist = [];
	#涉及大数据操作优先采用迭代器
	foreach ($datalist as $thread):
		// hook search_thread_format_before.php
		thread_format($thread);
		$thread['subject'] = search::highlight($thread['subject'], $keyword_arr);
		$threadlist[$thread['tid']] = $thread;
		unset($thread);
	endforeach;
elseif ($range == 0):
	$posts = 0;
	$search_colunm = 'SELECT `t`.*,`p`.`pid`,`p`.`last_update_date`,`p`.`message` as `message_fmt`  FROM';
	$search_join =  MyDB::tableqoute('post') . ' as `p` LEFT JOIN ' . MyDB::tableqoute('thread') . ' AS `t` ON `t`.`tid` = `p`.`tid` ';
	$where = MyDB::WHERE_AND(array('%p.message' => $keyword_arr, 'p.isfirst' => 1));
	$search_sql = $search_join . $where[0];
	$total = MyDB::execute('SELECT COUNT(*) FROM ' . $search_sql, $where[1], 7);
	$pagination = pagination(url('search-' . $keyword_decode . '-' . $range . '-{page}'), $total, $page, $pagesize);
	$datalist = MyDB::execute($search_colunm . $search_sql . MyDB::ORDER(['p.tid' => 'desc']) . MyDB::LIMIT($page, $pagesize), $where[1], 10);
	#涉及大数据操作优先采用迭代器
	foreach ($datalist as $post):
		// hook search_post_format_before.php
		post_format($post);
		$post['message_fmt'] = search::htmlformat($post['message_fmt']);
		$post['message_fmt'] = search::highlight($post['message_fmt'], $keyword_arr);
		$post['filelist'] = array();
		$post['floor'] = 0;
		$thread = thread_read_cache($post['tid']);
		$post['subject'] = search::highlight($thread['subject'], $keyword_arr);
		$postlist[$post['pid']] = $post;
		unset($post);
	endforeach;
endif;
if ($ajax):
	if ($threadlist):
		foreach ($threadlist as &$thread) $thread = thread_safe_info($thread);
		message(0, $threadlist);
	else:
		foreach ($postlist as &$post) $post = post_safe_info($post);
		message(0, $postlist);
	endif;
else:
	include _include(APP_PATH . 'plugin/xn_search_min/htm/search.htm');
endif;
