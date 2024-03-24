<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 数据库 数据表配置类
 */
namespace table;
class attach extends \lib\table
{
    public array $list = array();
    public array $settings = array();
    function __construct()
    {
        $this->table = 'attach';
        $this->indexkey = 'aid';
    }
    public function pids($pids,$not=false)
    {
        $query = ' WHERE '.$this->str_key('pid');
        $param = array();
        if(is_array($pids)):
            $value = array_values(array_unique($pids));
            $query .= $not?' NOT IN ':' IN ';
            $query .= '('.$this->str_fill_param($pids).')';
            $param = $value;
        else:
            $query .= $not ? ' <> ? ':' = ? ';
            $param = [$pids];
        endif;
        $result = $this->query($query,$param);
        $data = array();
        if(isset($this->indexkey)):
            foreach($result as $value):
                $key = $value[$this->indexkey];
                $pid = $value['pid'];
                $data[$pid][$key] = $value;
            endforeach;
            return $data;
        endif;
        return $result;
    }
    public function add_download($aid,$value=1)
    {
        return $this->query_update(' `downloads` =`downloads` + ? where `aid` = ?',array($value,$aid));
    }
}
