<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 数据库 数据表配置类
 */
namespace table;
use lib\table;
class settings extends table{
    function __construct()
    {
        $this->table = 'settings';
        $this->indexkey = 'name';
        $this->tableInfo = array(
            'name'  =>"varchar(32) NOT NULL",
            'value' =>"longtext NOT NULL",
        );
        $this->tableData = array(
            array('i18','zh-cn'),
            array('template_nocache',0),
            array('timezone','Asia/Shanghai'),
            array('thread_attach_cost',''),#array 附件付费设置
        );
    }
    public function all()
    {
        $result = $this->index2column('value');
        foreach($result as $key=>$value):
            if(is_numeric($value)):
                $result[$key] = intval($value);
            elseif(is_string($value)):
                $start = substr($value,0,2);
                if($start=='a:'&&substr($value,-1)=='}'):
                    $result[$key] = unserialize($value);
                endif;
            endif;
        endforeach;
        return $result;
    }
}