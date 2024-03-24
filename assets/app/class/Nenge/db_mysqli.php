<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 数据库驱动
 */

namespace Nenge;
class db_mysqli{
    private object $link;
    private array $settings;
    public string $version;
    public string $client;
    public $querylist = array();
	public function __construct(array $conf = array())
	{
        $myapp = APP::app();
        if(!\isExtension('mysqli')):
            return throw new \Exception($myapp->getLang('class_mysql').':extension=mysqli');
        endif;
        $this->settings = array(
            'host' => ini_get("mysqli.default_host"),
            'port' => ini_get("mysqli.default_port"),
            'charset' => 'utf8mb4',
            'name' =>'',
            'user' => ini_get("mysqli.default_user"),
            'pw' => ini_get("mysqli.default_pw"),
            'socket' => ini_get("mysqli.default_socket")
        );
        if(!empty($conf)):
            foreach($conf as $k=>$v):
                if(!empty($v)):
                    $this->settings[$k] = $v;
                endif;
            endforeach;
        endif;
            $this->link = new \mysqli(
                $this->settings['host'],
                $this->settings['user'],
                $this->settings['pw'],
                $this->settings['name'],
                $this->settings['port'],
                $this->settings['socket']
            );
            if ($this->link&&is_object($this->link)):
                if(!empty($this->link->error)):
                    $this->exception($this->link);
                endif;
                if($this->link->get_charset()->charset!=$this->settings['charset']):
                    $this->link->set_charset($this->settings['charset']);
                endif;
                $this->version = $this->link->server_info;
                $this->client = $this->link->client_info;
            endif;
    }
    public function get_collate()
    {
        if(empty($this->settings['collate'])):
            $this->settings['collate'] = $this->link->get_charset()->collation;
        endif;
        return $this->settings['collate'];
    }
    /**
     * 不会返回查询结果,但是返回处理信息
     */
    public function fetch_update(string $query,array $param)
    {
        $stmt = $this->get_Statement($query,$param);
        $row = $stmt->affected_rows;
        $stmt->free_result();
        $stmt->close();
        return $row;
    }
    /**
     * 不会返回查询结果,但是返回处理信息
     */
    public function fetch_update_multi(string $query,array $params)
    {
        $data = array();
        $time = microtime(1);
        $stmt = $this->link->prepare($query);
        $row = 0;
        foreach($params as $param):
            if(!empty($param)):
                $stmt->bind_param(...$this->getBindParam($param));
            endif;
            $stmt->execute();
            $this->exception($stmt);
            $data[] = $stmt->affected_rows;
            $row += $stmt->affected_rows;
            $stmt->free_result();
            $stmt->reset();
        endforeach;
        $stmt->close();
        $this->save_query($query,array('row'=>$row),microtime(1) - $time);
        return $data;
    }
    /**
     * 不会返回查询结果,但是返回处理信息
     */
    public function fetch_insert(string $query,array $param)
    {
        $stmt = $this->get_Statement($query,$param);
        $row = $stmt->insert_id;
        $stmt->free_result();
        $stmt->close();
        return $row;
    }
    /**
     * 不会返回查询结果,但是返回处理信息
     */
    public function fetch_insert_multi(string $query,array $params,$name=null)
    {
        $data = array();
        $time = microtime(1);
        $stmt = $this->link->prepare($query);
        $row = 0;
        foreach($params as $param):
            if(!empty($param)):
                $stmt->bind_param(...$this->getBindParam($param));
            endif;
            $stmt->execute();
            $this->exception($stmt);
            $data[] = $stmt->insert_id;
            $row += $stmt->affected_rows;
            $stmt->free_result();
            $stmt->reset();
        endforeach;
        $stmt->close();
        $this->save_query($query,array('row'=>$row),microtime(1) - $time);
        return $data;
    }
    /**
     * 获取返回值,对于insert返回处理信息
     */
    public function fetch_all($query,$param=array(),int $mode = 1)
    {
        $result =$this->get_Result($query,$param);
        if($result)return $this->fetch_result($result,$mode);
    }
    /**
     * 批量 预处理查询
     */
    public function fetch_multi($query,...$params)
    {
        $data = array();
        $time = microtime(1);
        $stmt = $this->link->prepare($query);
        $row = 0;
        foreach($params as $param):
            if(!empty($param)):
                $stmt->bind_param(...$this->getBindParam($param));
            endif;
            $stmt->execute();
            $this->exception($stmt);
            $row += $stmt->affected_rows;
            $result = $stmt->get_result();
            if($result):
                $data[] =  $result->fetch_all(MYSQLI_ASSOC);
                $result->free();
            endif;
            $stmt->reset();
        endforeach;
        $stmt->close();
        $this->save_query($query,array('row'=>$row),microtime(1) - $time);
        return $data;
    }
    /**
     * 对查询总结果进行处理,
     * 当$mode负数时,对返回结果添加第N列作为结果数据索引
     */
    public function fetch_result($result,$mode=1,...$more)
    {
        switch($mode){
            case 0:{
                $data = $this->call_result_method($result,'fetch_column',0);
                break;
            }
            case 2:{
                $data = $this->call_result_method($result,'fetch_all',MYSQLI_NUM);
                break;
            }
            case 3:{
                $data = $this->call_result_method($result,'fetch_all',MYSQLI_BOTH);
                break;
            }
            case 4:{
                $data = array();
                if(empty($more[0]))$more[0] = 0;
                while ($Name = $this->call_result_method($result,'fetch_column',$more[0])):
                    $data[] = $Name;
                endwhile;
                if(count($data)==1)$data = $data[0];
                break;
            }
            default:{
                if($mode<0):
                    $datas = $this->call_result_method($result,'fetch_all',MYSQLI_ASSOC);
                    $field = array_keys($datas[0])[-1-$mode];
                    $data = array();
                    if(!empty($field)):
                        foreach($datas as  $k=>$v):
                            $data[$v[$field]] = $v;
                        endforeach;
                    endif;
                else:
                    $data = $this->call_result_method($result,'fetch_all',MYSQLI_ASSOC);
                endif;
                break;
            }
        }
        $result->free();
        return $data;
        
    }
    /**
     * 查询结果第一行的数字索引结果
     */
    public function fetch_array($query,$param=array())
    {
        $result =  $this->get_Result($query,$param);
        return $this->call_result_method_free($result,'fetch_array',MYSQLI_NUM);
    }
    /**
     * 查询结果第一行的数字索引结果
     */
    public function fetch_both($query,$param=array())
    {
        $stmt = $this->get_Result($query,$param);
        return $this->call_result_method($stmt,'fetch_array',MYSQLI_BOTH);
    }
    /**
     * 查询结果第一行的字段索引结果
     */
    public function fetch_assoc($query,$param=array())
    {
        $result =  $this->get_Result($query,$param);
        return $this->call_result_method_free($result,'fetch_assoc');
    }
    /**
     * 查询结果第一行第一列结果
     */
    public function fetch_column($query,$param=array(),int $column = 0)
    {
        $result =  $this->get_Result($query,$param);
        return $this->call_result_method_free($result,'fetch_column',$column);
    }
    /**
     * 查询结果第一行以class形式返回
     */
    public function fetch_object($query,$param=array(),string $class = "stdClass",array $args = [])
    {
        $result =  $this->get_Result($query,$param);
        return $this->call_result_method_free($result,'fetch_column',$class,$args);
    }
    /**
     * 返回数据库名列表
     */
    public function show_database()
    {
        return $this->query('SHOW DATABASES;',4);
    }
    /**
     * 返回数据库名列表
     */
    public function show_table()
    {
        $str = '';
        if(isset($this->settings['name'])):
            $str = ' FROM `'.$this->settings['name'].'`';
        endif;
        return $this->query('SHOW TABLES '.$str.';',4);
    }
    /**
     * 返回用户权限信息
     */
    public function show_grant()
    {
        $users = $this->query('SELECT `user`,`host` FROM `mysql`.`user`',-1);
        $dbuers = $this->settings['user'];
        if(!empty($users[$dbuers])):
            return $this->query('SHOW grants for `'.$dbuers.'`@`'.$users[$dbuers]['host'].'`;',4);
        endif;
    }
    /**
     * 尝试返回用户可操作的数据库名
     */
    public function preg_table_name()
    {
        $result = $this->show_grant();
        if(!empty($result)):
            $data = [];
            if(strpos($result[0],'GRANT USAGE') !== false):
                foreach($result as $k=>$v):
                    if($k==0) continue;
                    preg_match('/ON `(.+?)`/',$v,$matchs);
                    $data[] = $matchs[1];
                endforeach;
            elseif(strpos($result[0],'GRANT OPTION')!==-false):
                return true;
            endif;
            return $data;
        endif;
    }
    /**
     * 切换默认数据库名
     */
    public function select_db($name)
    {
        return $this->link->select_db($name);
    }
    /**
     * 尝试刷新数据
     */
    public function refresh(int $options = MYSQLI_REFRESH_TABLES)
    {
        return $this->link->refresh($options);
    }
    public function getBindParam($params)
    {
        $type = '';
        $param = array();
        foreach($params as $value):
            if(is_array($value)) $value = serialize($value);
            $type .= is_float($value)?'d':(is_int($value)?'i':'s');
            $param[] = $value;
        endforeach;
        return array($type,...$param);
    }
    public function exception($stmt)
    {
        if($stmt->error):
            APP::app()->error_mysql(new \Exception($stmt->error,$stmt->errno));
        endif;
    }
    public function get_Statement(string $query,array $params)
    {
        $time = microtime(1);
        $stmt = $this->link->prepare($query);
        if(is_bool($stmt)):
            APP::app()->error_mysql(new \Exception($query,1054));
        endif;
        if(!empty($params)):
            $stmt->bind_param(...$this->getBindParam($params));
        endif;
        $stmt->execute();
        $this->exception($stmt);
        $this->save_query($query,array('row'=>$stmt->affected_rows),microtime(1) - $time);
        return $stmt;
    }
    public function get_Result($query,$param)
    {
        return $this->get_Statement($query,$param)->get_result();
    }
    /**
     * 记录查询日志
     */
    private function save_query($query,$info,$time)
    {
        $this->querylist[] = array_merge($info,array(
            'sql'=>$query,
            'time'=>round($time*1000,2)
        ));
    }
    public function exec($query)
    {
        $time = microtime(1);
        $row = 0;
        $result = $this->link->query($query);
        $this->exception($this->link);
        $row = $this->link->affected_rows;
        if(is_object($result)):
            $result->free();
        endif;
        $this->save_query($query,array('row'=>$row),microtime(1) - $time);
        return $row;
    }
    /**
     * 执行一次SQL
     */
    public function query(string $query,int $mode = 1,...$more)
    {
        $row = 0;
        $time = microtime(1);
        $result =  $this->link->query($query);
        $this->exception($this->link);
        $row = $this->link->affected_rows;
        $this->save_query($query,array('row'=>$row),microtime(1) - $time);
        if($result):
            return $this->fetch_result($result,$mode,...$more);
        endif;
        $this->save_query($query,array('row'=>$row),microtime(1) - $time);
    }
    /**
     * 执行多行SQL
     */
    public function multi_query(string $query,int $mode = 1,...$more)
    {
        $time = microtime(1);
        $row = 0;
        if ($this->link->multi_query($query)):
            $this->exception($this->link);
            $row = $this->link->affected_rows;
            $return = array();
            do {
                /* store first result set */
                if ($result = $this->link->store_result()):
                    $return[] = $this->fetch_result($result,$mode);
                endif;
                $this->link->more_results();
            } while ($this->link->next_result());
            $this->save_query($query,array('row'=>$row),microtime(1) - $time);
            return $return;
        else:
            $this->exception($this->link);
            $row = $this->link->affected_rows;
            $this->save_query($query,array('row'=>0),microtime(1) - $time);
            return array();
        endif;
    }
    /**
     * 返回最近插入的自增ID
     */
    public function insert_id()
    {
        return $this->link->insert_id;
    }
    /**
     * 返回最近影响行数
     */
    public function affected_rows()
    {
        return $this->link->affected_rows;
    }
    /**
     * 尝试执行结果处理函数
     */
    public function call_result_method($result,$method,...$arg)
    {
        $callable = array($result,$method);
        if($result&&is_callable($callable)):
            return call_user_func_array($callable,$arg);
        elseif(!empty($result)&&$method=='fetch_column'):
            #php less 8.1
            $result = $this->call_result_method($result,'fetch_array');
            if($result):
                if(empty($arg[0])):
                    $arg[0] = 0;
                elseif($arg[0]>count($result) - 1):
                    $arg[0] = count($result) - 1;
                endif;
                return $result[$arg[0]];
            endif;
        endif;
        return false;
    }
    /**
     * 尝试执行结果处理函数并释放内存
     */
    public function call_result_method_free($result,$method,...$arg)
    {
        $data = $this->call_result_method($result,$method,...$arg);
        if($result&&$data !== false):
            $result->free();
        endif;
        return $data;
    }
    /**
     * 销毁SQL链接
     */
	public function __destruct()
	{
        $this->link->close();
	}
}