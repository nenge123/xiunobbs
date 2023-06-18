<?php
namespace Nenge\table;
use Nenge\DB;
class table_attach extends base{
    public array $list = array();
    function __construct()
    {
        $this->table = 'attach';
        $this->indexkey = 'aid';
    }
    public function fetch_by_aids($aids)
    {
        if(empty($aids))return array();
        $result = $this->all(array('aid'=>$aids));
        if(!empty($result))$this->list+=$result;
        else $result = array();
        return $result;
    }
    public function fetch_by_pids($aids)
    {
        if(empty($aids))return array();
        $result = $this->all(array('pid'=>$aids));
        if(!empty($result))$this->list+=$result;
        else $result = array();
        return $result;
    }
    public function aids($aids)
    {
        if(is_numeric($aids)){
            if(isset($this->list[$aids])) return $this->list[$aids];
            else {
                $result = $this->fetch_by_aids($aids);
                if(!empty($result[$aids])) return $result[$aids];
            }
            return $result;
        }
        elseif(is_string($aids)) return array();
        $aids = array_unique($aids);
        $newaids = [];
        $result = [];
        foreach($aids as $k=>$v){
            if(empty($v))continue;
            $v = (int) $v;
            if(empty($this->list[$v])){
                $newaids[] = $v;
            }else{
                $result[$v] = $this->list[$v];
            }
        }
        return $result+$this->fetch_by_aids($newaids);
    }
}