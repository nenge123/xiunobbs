<?php
namespace Nenge\table;
use Nenge\DB;
class table_forum extends base{
    public $list = array();
    function __construct()
    {
        $this->table = 'forum';
        $this->indexkey = 'fid';
    }
    /*
    public function get_count_posts($fids=array())
    {
        if(empty($list))$this->list = $this->all();
        if(empty($fids)){
            return array_sum(array_column($this->list,'post'));
        }else{
            $num = 0;
            foreach($fids as $fid){
                $num += (int) (isset($this->list[$fid]['posts'])?$this->list[$fid]['posts']:0);
            }
            return $num;
        }
    }
    */
    public function get_count_threads($fids=array())
    {
        if(empty($list))$this->list = $this->all();
        if(empty($fids)){
            return array_sum(array_column($this->list,'threads'));
        }else{
            $num = 0;
            foreach($fids as $fid){
                $num += (int) (isset($this->list[$fid]['threads'])?$this->list[$fid]['threads']:0);
            }
            return $num;
        }
    }
    public function reset_count_threads()
    {
        //SELECT COUNT(tid) as `num`,`fid` FROM `bbs_thread` GROUP BY `fid`;
        $result = DB::t('thread')->get_count_fid();
        if(!empty($result)){
            $data = [];
            foreach($result as $k=>$v){
                $data[] = array($v['num'],$v['fid']);
            }
            if(!empty($data))return $this->connect()->result_all('UPDATE '.$this->quote_table().' SET `threads`=? WHERE `fid` = ?;',$data);
        }
    }

}