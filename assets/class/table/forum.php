<?php
namespace table;
use Nenge\DB;
class table_forum extends base{
    public array $list = array();
    public int $length;
    function __construct()
    {
        $this->table = 'forum';
        $this->indexkey = 'fid';
    }
    public function get_threads_count($fids=array())
    {
        if(is_numeric($fids)&&isset($this->list[$fids]['threads'])){
            return $this->list[$fids]['threads']?:0;
        }
        if(empty($this->list) || $this->length!=count($this->list)){
                $this->list = $this->all();
                $this->length = count($this->list);
        }
        if(is_array($fids)){
            $num = 0;
            foreach($fids as $fid){
                $num += (int) (isset($this->list[$fid]['threads'])?$this->list[$fid]['threads']:0);
            }
            return $num;
        }elseif(is_numeric($fids)&&isset($this->list[$fids]['threads'])){
            return $this->list[$fids]['threads']?:0;
        }
        return array_column($this->list,'threads','fid');
    }
    public function reset_threads_count()
    {
        //SELECT COUNT(tid) as `num`,`fid` FROM `bbs_thread` GROUP BY `fid`;
        $result = DB::t('thread')->get_fids_count();
        if(!empty($result)){
            $data = [];
            foreach($result as $k=>$v){
                $data[] = array($v['num'],$v['fid']);
            }
            if(!empty($data))return $this->connect()->result_all('UPDATE '.$this->quote_table().' SET `threads`=? WHERE `fid` = ?;',$data);
        }
    }

}