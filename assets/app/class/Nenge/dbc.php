<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 数据库管理中心
 */
namespace Nenge;
use lib\static_app;
class dbc{
    use static_app;
	public array $tablelist = array();
	public array $links = array();
    private array $settings;
	function __construct($conf = array())
	{
		self::$_app = $this;
        $this->setConf($conf);
	}
    public function setConf($conf)
    {
		if (!empty($conf[1])) {
			$this->settings['conf'] = $conf[1];
		}
		if (!empty($conf['map'])) {
			foreach ($conf['map'] as $k => $v) {
				if (!empty($conf[$v])) {
					$this->settings['map'][$k] = $conf[$v];
					$this->settings['map'][$k]['id'] = $v;
				}
			}
		}
    }
    /**
     * 获取配置信息
     */
    public function getConf($table=false)
    {
        if($table&&isset($this->settings['map'][$table])):
            return $this->settings['map'][$table];
        endif;
        return $this->settings['conf'];
    }
    /**
     * 链接数据库
     */
    public function getLink($table='')
    {
        $id = 1;
        $conf = $this->getConf($table);
        if(isset($conf['id'])):
            $id = $conf['id'];
        endif;
        if(empty($this->links[$id])):
            $this->links[$id] = new db_mysqli($conf);
        endif;;
        return $this->links[$id];
    }
    public function link()
    {
        return $this->getLink();
    }
    /**
     * 获取 数据表名前缀
     */
    public function getPre($table='')
    {
        $conf = $this->getConf($table);
        return $conf['pre'];
    }
    /**
     * 实例化 数据表名类
     */
    public function table($str)
    {
        $myapp = APP::app();
        if(empty($this->tablelist[$str])):
            list($table,$plugin) = $myapp->get_name_of_plugin($str);
            $class = '\\table\\'.$table;
            if (!class_exists($class,false)):
                if (!empty($plugin)) {
                    $path = $myapp->get_plugin_dir($plugin,'table');
                } else {
                    $path =  $myapp->data['path']['table'];
                }
                $path .= str_replace('\\',DIRECTORY_SEPARATOR,$table) . '.php';
                if(is_file($path)):
                    include_once($path);
                else:
                    return $this->tablelist[$str] = new class(basename($table)) extends \lib\table{
                        public function __construct($table)
                        {
                            $this->table = $table;
                        }
                    };
                endif;
            endif;
            $this->tablelist[$str] = new $class;
        endif;
        return $this->tablelist[$str];
    }
    public function exec($sql)
    {
        return $this->getLink()->exec($sql);
    }
    public function query($sql,int $mode = 1,...$more)
    {
        return $this->getLink()->query($sql,$mode,...$more);
    }
    public function multi_query($sql,int $mode = 1,...$more)
    {
        return $this->getLink()->multi_query($sql,$mode,...$more);
    }
    /**
     * 获取SQL查询信息
     */
	public function getSql()
	{
		if (!empty($this->links)) {
			$sql = [];
			foreach ($this->links as $link):
                if(!empty($link->querylist)):
                    $sql[] = $link->querylist;
                endif;
			endforeach;
			return  array_merge(...$sql);
		}
	}
    public function __destruct(){
        foreach($this->links as $link):
            unset($link);
        endforeach;
        foreach($this->tablelist as $table):
            unset($table);
        endforeach;
        unset($this->links);
        unset($this->tablelist);
    }

}