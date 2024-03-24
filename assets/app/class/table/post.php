<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 数据库 数据表配置类
 */
namespace table;
class post extends \lib\table{
    function __construct()
    {
        $this->table = 'post';
        $this->indexkey = 'pid';
        /*
        $this->tableInfo = array(        
            "pid"=>"int UNSIGNED NOT NULL AUTO_INCREMENT",
            "tid"=>"int UNSIGNED DEFAULT '0'",
            "uid"=>"int UNSIGNED NOT NULL DEFAULT '0'",
            "createdate"=>"int UNSIGNED NOT NULL DEFAULT '0'",
            "userip"=>"int UNSIGNED NOT NULL DEFAULT '0'",
            "images"=>"smallint UNSIGNED NOT NULL DEFAULT '0'",
            "files"=>"smallint UNSIGNED NOT NULL DEFAULT '0'",
            "doctype"=>"tinyint UNSIGNED NOT NULL DEFAULT '0'",
            #"quotepid"=>"int UNSIGNED NOT NULL DEFAULT '0'",
            "message"=>"longtext NOT NULL",
        );
        $this->tableAlter = array(
            "PRIMARY KEY (`pid`)",
            "KEY `tid` (`tid`)",
        );
        */
    }
    public function getlist()
    {
        $sql = '';
        $tid = router_value('tid');
        $pid = router_value('firstpid');
        $params[] = $tid;
        $params[] = $pid;
        $sql .= ' WHERE `tid` = ? AND `pid` <> ? ';
        $order = router_value('order');
        $page = router_value('page');
        $limit = router_value('limit');
        $total = $this->count($sql.';',$params);
        $pagecount = ceil($total/$limit);
        if($page>$pagecount)$page = $pagecount;
        $start = ($page-1)*$limit;
        switch($order):
            case 'pid':
                $sql .= 'ORDER BY `pid` DESC ';
                break;
            default:
                $sql .= 'ORDER BY `pid` ASC ';
            break;
        endswitch;
        $sql .= ' LIMIT '.$start.','.$limit.';';
        return array(
            'list'=>$this->index2array($sql,$params),
            'limit'=>$limit,
            'total'=>$total,
            'page'=>$page,
            'pagecount'=>$pagecount,
            'order'=>$order,
            'pagination' => pagination($total,$page,$limit)
        );
    }
}