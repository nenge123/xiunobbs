<?php

/*
	Xiuno BBS 4.0 插件实例：搜索插件卸载
	admin/plugin-unstall-xn_search.htm
*/

!defined('DEBUG') AND exit('Forbidden');
//浅唱修改开始
$tablepre = $db->tablepre;
$r = db_exec("DROP TABLE IF EXISTS {$tablepre}search_log;");
$r === FALSE AND message(-1, '卸载搜索记录表search_log失败');
//浅唱修改结束
?>