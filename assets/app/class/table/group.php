<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 数据库 数据表配置类
 */
namespace table;
use lib\table;
class group extends table{
    function __construct()
    {
        $this->table = 'group';
        $this->indexkey = 'gid';
        $this->tableInfo = array(
            'gid'           =>"SMALLINT UNSIGNED NOT NULL               COMMENT '用户组ID'",
            'name'          =>"char(20) NOT NULL                        COMMENT '用户组名称'",
            'credits'       =>"mediumint UNSIGNED NOT NULL DEFAULT '0'  COMMENT '升级积分'",
            'read'          =>"tinyint UNSIGNED NOT NULL DEFAULT '0'    COMMENT '阅贴权限'",
            'thread'        =>"tinyint UNSIGNED NOT NULL DEFAULT '0'    COMMENT '发帖权限'",
            'post'          =>"tinyint UNSIGNED NOT NULL DEFAULT '0'    COMMENT '回帖权限'",
            'attach'        =>"tinyint UNSIGNED NOT NULL DEFAULT '0'    COMMENT '上传权限'",
            'down'          =>"tinyint UNSIGNED NOT NULL DEFAULT '0'    COMMENT '下载权限'",
            'top'           =>"tinyint UNSIGNED NOT NULL DEFAULT '0'    COMMENT '置顶权限'",
            'update'        =>"tinyint UNSIGNED NOT NULL DEFAULT '0'    COMMENT '修改权限'",
            'delete'        =>"tinyint UNSIGNED NOT NULL DEFAULT '0'    COMMENT '删帖权限'",
            'move'          =>"tinyint UNSIGNED NOT NULL DEFAULT '0'    COMMENT '移动贴权限'",
            'banuser'       =>"tinyint UNSIGNED NOT NULL DEFAULT '0'    COMMENT '封禁用户'",
            'deleteuser'    =>"tinyint UNSIGNED NOT NULL DEFAULT '0'    COMMENT '删除用户'",
            'viewip'        =>"tinyint UNSIGNED NOT NULL DEFAULT '0'    COMMENT '查看IP'"
        );
        $this->tableData = array(
            array(0,    '云游游客',   0,      1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0),
            array(1,    '超级管理',   0,      1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
            array(2,    '超级版主',   0,      1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
            array(4,    '论坛版主',   0,      1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 1),
            array(5,    '实习版主',   0,      1, 1, 1, 1, 1, 1, 1, 0, 1, 0, 0, 0),
            array(6,    '验证用户',   0,      1, 0, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0),
            array(7,    '封禁用户',   0,      0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
            array(8,    '普通用户',   0,      0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
            array(101,  '新手上路',   0,      1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0),
            array(102,  '江湖新秀',   50,     1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0),
            array(103,  '武林高手',   500,    1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0),
            array(104,  '武林至尊',   5000,   1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0),
            array(105,  '武林神话',   50000,  1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0)
        );
    }
    public function all()
    {
        return $this->index2array();
    }
    public function order()
    {
        return $this->index2array(' ORDER BY `creditsfrom` ASC');
    }
}