<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 数据库 数据表配置类
 */
namespace table;
use lib\table;
class access extends table{
    function __construct()
    {
        $this->table = 'forum_access';
        $this->indexkey = 'fid';
        $this->tableInfo = array(
            "fid"   =>  "smallint UNSIGNED NOT NULL DEFAULT '0' COMMENT '板块ID'",
            "gid"   =>  "tinyint UNSIGNED NOT NULL DEFAULT '0'  COMMENT '用户组ID'",
            "read"  =>  "tinyint UNSIGNED NOT NULL DEFAULT '0'  COMMENT '阅读权限'",
            "thread"=>  "tinyint UNSIGNED NOT NULL DEFAULT '0'  COMMENT '发帖权限'",
            "post"  =>  "tinyint UNSIGNED NOT NULL DEFAULT '0'  COMMENT '回帖权限'",
            "attach"=>  "tinyint UNSIGNED NOT NULL DEFAULT '0'  COMMENT '上传权限'",
            "down"  =>  "tinyint UNSIGNED NOT NULL DEFAULT '0'  COMMENT '下载权限'"
        );
    }
}