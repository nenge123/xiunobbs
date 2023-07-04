<?php
namespace table;
class table_user extends base{
    public array $list = array();
    function __construct()
    {
        $this->table = 'user';
        $this->indexkey = 'uid';
    }
    public function fetch_by_uids($uids)
    {
        if(empty($uids))return array();
        $result = $this->all(array('uid'=>$uids));
        if(!empty($result))$this->list+=$result;
        else $result = array();
        return $result;
    }
    public function uids($uids)
    {
        if(is_numeric($uids)){
            if(isset($this->list[$uids])) return $this->list[$uids];
            else {
                $result = $this->fetch_by_uids($uids);
                if(!empty($result[$uids])) return $result[$uids];
            }
            return $result;
        }
        elseif(is_string($uids)) return array();
        $uids = array_unique($uids);
        $newuids = [];
        $result = [];
        foreach($uids as $k=>$v){
            if(empty($v))continue;
            $v = (int) $v;
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
    public function online($time,$limit=15)
    {
        $result = $this->all(array('>:login_date'=>$time),array('order'=>array('login_date'=>'DESC'),'limit'=>$limit));
        if(!empty($result)){
            $this->list += $result;
        }
        return $result;
    }
}