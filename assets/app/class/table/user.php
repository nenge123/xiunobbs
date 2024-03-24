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
class user extends table{
    public array $list = array();
    function __construct()
    {
        $this->table = 'user';
        $this->indexkey = 'uid';
        $this->tableInfo = array(
            "uid"=>"mediumint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户编号'",
            "gid"=>"smallint UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户组编号'",
            "email"=>"char(40) NOT NULL DEFAULT '' COMMENT '邮箱'",
            "username"=>"char(32) NOT NULL DEFAULT '' COMMENT '用户名'",
            "password"=>"varchar(255) NOT NULL DEFAULT '' COMMENT '密码'",
            #"salt"=>"char(16) NOT NULL DEFAULT '' COMMENT '密码混杂'",
            #"mobile"=>"char(11) NOT NULL DEFAULT '' COMMENT '手机号'",
            #"qq"=>"char(15) NOT NULL DEFAULT '' COMMENT 'QQ'",
            #"threads"=>"int NOT NULL DEFAULT '0' COMMENT '发帖数'",
            #"posts"=>"int NOT NULL DEFAULT '0' COMMENT '回帖数'",
            "credits"=>"int NOT NULL DEFAULT '0' COMMENT '积分'",
            #"golds"=>"int NOT NULL DEFAULT '0' COMMENT '金币'",
            #"rmbs"=>"int NOT NULL DEFAULT '0' COMMENT '人民币'",
            "create_ip"=>"int UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时IP'",
            "create_date"=>"int UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间'",
            "login_ip"=>"int UNSIGNED NOT NULL DEFAULT '0' COMMENT '登录时IP'",
            "login_date"=>"int UNSIGNED NOT NULL DEFAULT '0' COMMENT '登录时间'",
            "logins"=>"int UNSIGNED NOT NULL DEFAULT '0' COMMENT '登录次数'",
            "avatar"=>"int UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户最后更新图像时间'",
        );
        $this->tableAlter = array(
            "PRIMARY KEY (`uid`)",
            "UNIQUE KEY `username` (`username`)",
            "UNIQUE KEY `email` (`email`)",
        );
    }
    public function online($time,$limit=15)
    {
        return self::link()->fetch_all($this->str_select(array('uid','username','login_date')).' WHERE `login_date` > ? ORDER BY `login_date` DESC LIMIT ?',[$time,$limit]);
    }
    public function safe_datas($uids)
    {
        $result = $this->values($uids);
        foreach($result as $k=>$v){
            unset($result[$k]['password']);
        }
        return $result;
    }
    public function threads($uids)
    {
        return $this->values($uids,false,array('uid','gid','username','credits'));
    }
    public function safe_all($uids,$select=false)
    {
        $result = $this->values($uids,$select);
        foreach($result as $uid=>$value):
            #$result[$uid] += $this->parse_ip($result[$uid]);
            unset($result[$uid]['password']);
            #unset($result[$uid]['email']);
            unset($result[$uid]['salt']);
            #unset($result[$uid]['login_ip']);
            #unset($result[$uid]['create_ip']);
        endforeach;
        return $result;
    }
    public function cost($data,$uid)
    {
        $keys =  array_keys($data);
        $values = array_values($data);
        $values[] = $uid;
        return $this->query_update(implode(',',array_map(fn($v)=>$this->quote($v).'='.$this->quote($v).' - ? ',$keys)).' WHERE `uid` = ?',$values);
    }
    public function win($data,$uid)
    {
        $keys =  array_keys($data);
        $values = array_values($data);
        $values[] = $uid;
        return $this->query_update(implode(',',array_map(fn($v)=>$this->quote($v).'='.$this->quote($v).' + ? ',$keys)).' WHERE `uid` = ?',$values);
    }
}