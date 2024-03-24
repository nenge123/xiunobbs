<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 数据库 数据表配置类
 */
namespace table;
class threadtop extends \lib\table{
    function __construct()
    {
        $this->table = 'thread_top';
        $this->indexkey = 'fid';
    }
}