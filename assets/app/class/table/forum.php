<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 数据库 数据表配置类
 */
namespace table;
use Nenge\APP;
use lib\table;
class forum extends table{
    function __construct()
    {
        $this->table = 'forum';
        $this->indexkey = 'fid';
        $this->tableInfo = array(
            'fid'=>"smallint UNSIGNED NOT NULL AUTO_INCREMENT  COMMENT '查看IP'",
            'fup'=>"smallint UNSIGNED DEFAULT '0' COMMENT '查看IP'",
            'name'=>"char(64) NOT NULL DEFAULT '' COMMENT '查看IP'",
            'rank'=>"tinyint UNSIGNED DEFAULT '0'",
            'icon'=>"int UNSIGNED DEFAULT '0'",
            #"threads"       =>"mediumint UNSIGNED NOT NULL DEFAULT '0'",
            #"todayposts"    =>"mediumint UNSIGNED NOT NULL DEFAULT '0'",
            #"todaythreads"  =>"mediumint UNSIGNED NOT NULL DEFAULT '0'",
            'orderby'=>"tinyint DEFAULT '0'",
            'moduids'=>"char(120) DEFAULT ''",
            'brief'=>"text",
            'announcement'=>"text",
            'seo_title'=>"char(64) DEFAULT ''",
            'seo_keywords'=>"char(64) DEFAULT ''",
            'display'=>"TINYINT UNSIGNED NOT NULL DEFAULT '1'"

        );
        $this->tableData = array(
            array(1, 0, '默认版块介绍', 0, 0, 0, '', '', '', '', '',1)
        );
    }
    public function all()
    {
        return $this->index2array();
    }

}