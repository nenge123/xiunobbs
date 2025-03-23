<?php

/*
	Xiuno BBS 4.0 插件：敏感词过滤安装
	admin/plugin-install-qt_sensitive_word.htm
*/

!defined('DEBUG') AND exit('Forbidden');

// 初始化
$kv = kv_get('qt_sensitive_word');
if(!$kv) {
	$kv = array(
		'username_sensitive_words' => array(),
		'post_sensitive_words' => array(),
	);
	kv_set('qt_sensitive_words', $kv);
}

?>