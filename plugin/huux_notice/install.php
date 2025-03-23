<?php

/*
	Xiuno BBS 4.0 消息
*/

!defined('DEBUG') and exit('Forbidden');

$tablepre = $db->tablepre;
$sql = "CREATE TABLE IF NOT EXISTS {$tablepre}notice (
	nid int(11) unsigned NOT NULL auto_increment, 
	fromuid int(11) unsigned NOT NULL default '0',	
	recvuid int(11) unsigned NOT NULL default '0',	 
	create_date int(11) unsigned NOT NULL default '0',	
	isread tinyint(3) unsigned NOT NULL default '0',
	type tinyint(3) unsigned NOT NULL default '0',	
	message longtext NOT NULL,				      
	PRIMARY KEY (nid),
	KEY (fromuid, type),
	KEY (recvuid, type)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
db_exec($sql);
$user_columns = MyDB::t('user')->columns();
if (!in_array('notices', $user_columns)):
	// 消息数
	$sql = "ALTER TABLE {$tablepre}user ADD COLUMN notices mediumint(8) unsigned NOT NULL default '0';";
	db_exec($sql);
endif;
if (!in_array('unread_notices', $user_columns)):
	// 未读的消息数
	$sql = "ALTER TABLE {$tablepre}user ADD COLUMN unread_notices mediumint(8) unsigned NOT NULL default '0';";
	db_exec($sql);
endif;
