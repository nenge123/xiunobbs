<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 数据库驱动
 */

namespace Nenge;
class mysqlpdo{
    public object $link;
    private array $settings;
    public string $version;
    public string $client;
    public $querylist = array();
	public function __construct(array $conf = array())
	{
        $myapp = APP::app();
        if(!\isExtension('pdo_mysql')):
            return throw new \Exception($myapp->getLang('class_mysql').':extension=pdo_mysql');
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
        $time = microtime(true);
        $dsn = 'mysql:dbname='.$this->settings['name'].';host='.$this->settings['host'].';port='.$this->settings['port'];
        $this->link = new \PDO(
            $dsn,
            $this->settings['user'],
            $this->settings['pw'],
            array(
                \PDO::ATTR_TIMEOUT => 3,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_EMULATE_PREPARES => true,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::MYSQL_ATTR_INIT_COMMAND =>'SET NAMES '.$this->settings['charset'].($this->settings['collate']?' COLLATE '.$this->settings['collate']:''),
            )
        );
        $this->save_query('pdo::init',[],microtime(true)- $time);
    }
    public function get_collate()
    {
        if(empty($this->settings['collate'])):
            $this->settings['collate'] = $this->query('SHOW VARIABLES LIKE \'collation_database\';',4,1);
        endif;
        return $this->settings['collate'];
    }
    public function get_Statement($query,$param)
    {
        $time = microtime(true);
        $stmt = $this->link->prepare($query);
        $this->setBindParam($param,$stmt);
        $stmt->execute();
        $this->save_query($query,array('row'=>$stmt->rowCount()),microtime(1) - $time);
        return $stmt;
    }
    /**
     * 不会返回查询结果,但是返回处理信息
     */
    public function fetch_update(string $query,array $param)
    {
        $stmt = $this->get_Statement($query,$param);
        return $stmt->rowCount();
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
                $this->setBindParam($param,$stmt);
            endif;
            $stmt->execute();
            $data[] = $stmt->rowCount();
            $row += $stmt->rowCount();
            $stmt->closeCursor();
        endforeach;
        $this->save_query($query,array('row'=>$row),microtime(1) - $time);
        unset($stmt);
        return $data;
    }
    /**
     * 不会返回查询结果,但是返回处理信息
     */
    public function fetch_insert(string $query,array $param,$name=null)
    {
        $this->get_Statement($query,$param);
        return $this->link->lastInsertId($name);
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
                $this->setBindParam($param,$stmt);
            endif;
            $stmt->execute();
            $data[] = $this->link->lastInsertId($name);
            $row += $stmt->rowCount();
            $stmt->closeCursor();
        endforeach;
        $this->save_query($query,array('row'=>$row),microtime(1) - $time);
        unset($stmt);
        return $data;
    }
    /**
     * 获取返回值,对于insert返回处理信息
     */
    public function fetch_all($query,$param=array(),int $mode = 1)
    {
        $stmt = $this->get_Statement($query,$param);
        return $this->fetch_result($stmt,$mode);
    }
    /**
     * 批量 预处理查询
     */
    public function fetch_multi($query,...$params)
    {
        return $this->get_prepare_method('fetchAll',$query,...$params);
    }
    /**
     * 对查询总结果进行处理,
     * 当$mode负数时,对返回结果添加第N列作为结果数据索引
     */
    public function fetch_result($result,$mode=1,...$more)
    {
        switch($mode){
            case 0:{
                $data = $this->call_result_method($result,'fetchColumn',0);
                break;
            }
            case 2:{
                $data = $this->call_result_method($result,'fetchAll',\PDO::FETCH_NUM);
                break;
            }
            case 3:{
                $data = $this->call_result_method($result,'fetchAll',\PDO::FETCH_BOTH);
                break;
            }
            case 4:{
                $data = array();
                if(empty($more[0]))$more[0] = 0;
                while ($Name = $this->call_result_method($result,'fetchColumn',$more[0])):
                    $data[] = $Name;
                endwhile;
                if(count($data)==1)$data = $data[0];
                break;
            }
            default:{
                if($mode<0):
                    $datas = $this->call_result_method($result,'fetchAll',\PDO::FETCH_ASSOC);
                    $field = array_keys($datas[0])[-1-$mode];
                    $data = array();
                    if(!empty($field)):
                        foreach($datas as  $k=>$v):
                            $data[$v[$field]] = $v;
                        endforeach;
                    endif;
                else:
                    $data = $this->call_result_method($result,'fetchAll',\PDO::FETCH_ASSOC);
                endif;
                break;
            }
        }
        unset($result);
        return $data;
        
    }
    /**
     * 查询结果第一行的数字索引结果
     */
    public function fetch_array($query,$param=array())
    {
        $stmt = $this->get_Statement($query,$param);
        return $this->call_result_method($stmt,'fetch',\PDO::FETCH_NUM);
    }
    /**
     * 查询结果第一行的数字索引结果
     */
    public function fetch_both($query,$param=array())
    {
        $stmt = $this->get_Statement($query,$param);
        return $this->call_result_method($stmt,'fetch',\PDO::FETCH_BOTH);
    }
    /**
     * 查询结果第一行的字段索引结果
     */
    public function fetch_assoc($query,$param=array())
    {
        $stmt = $this->get_Statement($query,$param);
        return $this->call_result_method($stmt,'fetch',\PDO::FETCH_ASSOC);
    }
    /**
     * 查询结果第一行第一列结果
     */
    public function fetch_column($query,$param=array(),int $column = 0)
    {
        $stmt = $this->get_Statement($query,$param);
        return $this->call_result_method($stmt,'fetchColumn',$column);
    }
    /**
     * 查询结果第一行以class形式返回
     */
    public function fetch_object($query,$param=array(),string $class = "stdClass",array $args = [])
    {
        $stmt = $this->get_Statement($query,$param);
        return $this->call_result_method($stmt,'fetchObject',$class,$args);
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
        return $this->link->exec('use `'.$name.'`;');
    }
    /**
     * 尝试刷新数据
     */
    public function refresh()
    {
        return $this->link->exec('FLUSH TABLES;');
    }
    public function setBindParam($params,&$stmt)
    {
        $index = 1;
        foreach($params as $value):
            if(is_array($value)) $value = serialize($value);
            $param[] = $value;
            $stmt->bindParam($index,$value,is_int($value)?\PDO::PARAM_INT:\PDO::PARAM_STR);
            $index+=1;
        endforeach;
    }
    public function get_prepare_method($method,$query,...$params)
    {
        $time = microtime(1);
        $stmt = $this->link->prepare($query);
        $data = [];
        foreach($params as $param):
            if(!empty($param)):
                $this->setBindParam($param,$stmt);
            endif;
            $stmt->execute();
            $result = $this->call_result_method($stmt,$method);
            $info = array('row'=>$stmt->rowCount());
            if(!empty($result)):
                $data[] = $result;
            endif;
            $stmt->closeCursor();
            $this->save_query($query,$info,microtime(1) - $time);
        endforeach;
        unset($stmt);
        return $data;

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
        $row = $this->link->exec($query);
        $this->save_query($query,array('row'=>$row),microtime(1) - $time);
        return $row;
    }
    /**
     * 执行一次SQL
     */
    public function query(string $query,int $mode = 1,...$more)
    {
        $time = microtime(1);
        $stmt =  $this->link->query($query);
        $row = $stmt->rowCount();
        $this->save_query($query,array('row'=>$row),microtime(1) - $time);
        return $this->fetch_result($stmt,$mode,...$more);
    }
    /**
     * 执行多行SQL
     */
    public function multi_query(string $query,int $mode = 1)
    {
        $time = microtime(1);
        if ($Statement = $this->link->query($query)):
            $return = array();
            $row = 0;
            do {
                $row += $Statement->rowCount();
                $return[] = $this->fetch_result($Statement,$mode);
            } while ($Statement->nextRowset());
            $this->save_query($query,array('row'=>$row),microtime(1) - $time);
            return $return;
        endif;
    }
    /**
     * 尝试执行结果处理函数
     */
    public function call_result_method($result,$method,...$arg)
    {
        $callable = array($result,$method);
        if($result&&is_callable($callable)):
            return call_user_func_array($callable,$arg);
        endif;
        return false;
    }
    /**
     * 销毁SQL链接
     */
	public function __destruct()
	{
        unset($this->link);
	}
}