<?php
namespace Nenge\table;
use Nenge\DB;
class table_post extends base{
    function __construct()
    {
        $this->table = 'post';
        $this->indexkey = 'pid';
    }
    public function get_count_tid($tid=array(),$page=0)
    {
        $link = $this->connect();
        if(empty($tid)) {
            $limit = '';
            if($page>0){
                $page -=1;
                $limit = ' LIMIT '.($page*500).',500';
            }
            return $link->result_all('SELECT COUNT(`pid`) as `num`,`tid` FROM '.$this->quote_table().' GROUP BY `tid`'.$limit.';');
        }
        $where = '`tid`=?';
        if(is_array($tid)){
            $where = '`tid` IN('.implode(',',array_fill(0,count($tid),'?')).')';
        }
        if(is_array($tid))return $link->result_all('SELECT COUNT(`pid`) as `num`,`tid` FROM '.$this->quote_table().' WHERE '.$where,$tid);
        return $link->result_first('SELECT COUNT(`pid`) as `num` FROM '.$this->quote_table().' WHERE '.$where,$tid);
    }
    public function postlist($tid,$page=1,$size=15,$order='ASC')
    {
        $limit_start = ($page-1)*15;
        $limit_end = $size;
        $link = $this->connect();
        if($order=='ASC'){            
            if($page==1){
                $limit_end +=1;
            }else{
                $limit_start+=1;
            }
        }else{
            $order = 'DESC';
        }
        $result = $link->result_all('SELECT * FROM '.$this->quote_table().' WHERE `tid`=? ORDER BY `create_date` '.$order,array($tid));
        return $result;

    }
}