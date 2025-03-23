<?php

/*
	Xiuno BBS 4.0 插件实例：搜索
	admin/plugin-install-xn_search.htm
*/

!defined('DEBUG') AND exit('Forbidden');
$tablepre = $db->tablepre;
$sql = "CREATE TABLE IF NOT EXISTS {$tablepre}search_log (
  `clientip` varchar(40) NOT NULL COMMENT '搜索时IP',
  `datetime` int(13) NOT NULL COMMENT '搜索时间',
  `userid` int(11) NOT NULL COMMENT '用户编号',
  `type` int(1) NOT NULL COMMENT '搜索类型',
  `content` varchar(255) NOT NULL COMMENT '搜索内容'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";
 MyDB::exec($sql);
?>