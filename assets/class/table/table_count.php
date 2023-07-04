<?php
namespace table;
use Nenge\APP;
use Nenge\DB;
class table_table_count extends base{
    function __construct()
    {
        $this->table = 'table_count';
        $this->indexkey = '';
    }
    public function get_count($timestr='')
    {
        if(empty($timestr))$timestr =  date('Y-n-j',APP::app()->data['time']);
        $result = $this->all(array('date'=>'2022-10-14'));
        $data = array(
            'post'=>0,
            'thread'=>0,
            'user'=>0,
        );
        foreach($result as $k=>$v){
            $data[$v['table']] = $v['count'];
        }
        return $data;
    }
    public function add_count($data,$timestr='')
    {
        if(empty($timestr))$timestr =  date('Y-n-j',APP::app()->data['time']);
        $param = [];
        foreach($data as $k=>$v){
            $param[] = $timestr;
            $param[] = $k;
            $param[] = $v;
        }
        $link = $this->connect();
        $sql = 'INSERT INTO '.$this->quote_table().' (`date`, `table`, `count`) VALUES '.implode(',',array_fill(0,count($data),'(?,?,?)')).' AS `new`(`a`,`b`,`c`) ON DUPLICATE KEY UPDATE `count`=`count`+`new`.`c`;';
        $result = $link->result_query($sql,$param);
        if(!empty($result)){
            return $result['rows'];
        }
    }
}