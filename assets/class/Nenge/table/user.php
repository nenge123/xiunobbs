<?php
namespace Nenge\table;
class table_user extends base{
    public $list = [];
    function __construct()
    {
        $this->table = 'user';
        $this->indexkey = 'uid';
    }
    public function fetch_by_uids($uids)
    {
        if(empty($uids))return array();
        $result = $this->all(array('uid'=>$uids));
        $this->list+=$result;
        return $result;
    }
    public function uids($uids)
    {
        if(is_int($uids)) $uids = array($uids);
        $newuids = [];
        $result = [];
        foreach($uids as $k=>$v){
            if(empty($this->list[$v])){
                $newuids[] = $v;
            }else{
                $result[$v] = $this->list[$v];
            }
        }
        return $result+$this->fetch_by_uids($newuids);
    }
    public function uid($uid)
    {
        if(isset($this->list[$uid])) return $this->list[$uid];
        $this->list[$uid] = $this->fetch(array('uid'=>$uid));
        return $this->list[$uid];
    }
}