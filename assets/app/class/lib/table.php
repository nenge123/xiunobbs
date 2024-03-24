<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 数据库驱动
 */
namespace lib;
abstract class table{
	public string $pre;
	public string $table;
	public string $dbname;
	public string $indexkey;
	public array  $tableInfo;
    public array  $tableData;
    public array  $tableAlter;
	public array  $tableValues;
    public string $tableEngine;
    public string $tableCharset;
    public string $tableCollate;
    /**
     * 返回数据链接驱动 Nenge\db_mysqli
     */
    protected function link()
    {
        return \Nenge\dbc::app()->getLink($this->table);
    }
    /**
     * 字段列表
     */
    public function field()
    {
        return array_keys($this->field_array());
    }
    /**
     * 获取字段信息
     */
    public function field_exec()
    {
        $this->tableValues = array();
        if($result = self::link()->query('SHOW COLUMNS FROM '.$this->str_tablename(),1)):
            foreach($result as $items):
                $key = $items['Field'];
                if(isset($items['Default'])):
                    $value = $items['Default'];
                elseif($items['Null']=='YES'):
                    $value = NULL;
                else:
                    $value = '';
                endif;
                if(stripos($items['Type'],'int')!==false):
                    $value = intval($value);
                endif;
                $this->tableValues[$key] = $value;
            endforeach;
        endif;
    }
    /**
     * 字段默认值
     */
    public function field_array()
    {
        if(!isset($this->tableValues)):
            if(!empty($this->tableInfo)):
                $this->tableValues = array();
                foreach($this->tableInfo as $key=>$v):
                    $value = '';
                    if(preg_match("/DEFAULT\s+'(.+?)'/",$v,$matches)):
                        $value = $matches[1];
                    endif;
                    if(strpos($v,'int')!==false):
                        $value = intval($value);
                    endif;
                    $this->tableValues[$key] = $value;
                endforeach;
            endif;
            if(empty($this->tableInfo)):
                self::field_exec();
            endif;
        endif;
        return $this->tableValues;
    }
    /**
     * TRUNCATE方式清空数据库
     */
    protected function act_clear()
    {
        return self::link()->query('TRUNCATE TABLE '.$this->str_tablename());
    }
    /**
     * DELETE方式清空数据表
     */
    protected function act_delete()
    {
        return self::link()->query($this->str_delete());
    }
    /**
     * 删除数据表
     */
    protected function act_drop()
    {
        return self::link()->query('DROP TABLE IF EXISTS '.$this->str_tablename());
    }
    /**
     * 刷新数据表
     */
    public function act_flush()
    {
        return self::link()->query('FLUSH TABLE '.$this->str_tablename(),0);
    }
    /**
     * 优化表
     */
    public function act_optimize()
    {
        return self::link()->query('OPTIMIZE TABLE '.$this->str_tablename());
    }
    /**
     * 检查表
     */
    public function act_check()
    {
        return self::link()->query('CHECK TABLE '.$this->str_tablename());
    }
    public function act_checksum()
    {
        return self::link()->query('CHECKSUM TABLE '.$this->str_tablename());
    }
    public function act_analyze()
    {
        return self::link()->query('ANALYZE TABLE '.$this->str_tablename());
    }
    /**
     * 整理表
     */
    public function act_engine(string $engine='')
    {
        if(empty($engine))$engine = $this->get_engine();
        return self::link()->query($this->str_alter().' ENGINE='.$engine,0);
    }
    /**
     * 给字符增加反引号
     */
    public function quote($str)
    {
        return !empty($str)?'`'.$str.'`':'';
    }
    /**
     * 给字段生成带表名反引号
     */
    public function str_key($str=false)
    {
        if(is_string($str)) return $this->str_table().'.'.$this->quote($str);
        return $this->str_table().'.'.$this->quote($this->indexkey);
    }
    public function str_fullkey(string $str)
    {
        return $this->str_dbname().'.'.$this->str_table().'.'.$this->quote($str);
    }
    /**
     * 返回带反引号的数据库名
     */
	public function str_dbname()
	{
		return $this->quote($this->get_dbname());
	}
    /**
     * 返回含有数据库名前缀的表名
     */
    public function str_tablename()
    {
        return $this->str_dbname().'.'.$this->str_table();
    }
    /**
     * 返回带反引号的表明
     */
    public function str_table()
    {
        return $this->quote($this->get_tablename());
    }
    /**
     * 返回SELECT基本语句
     */
    public function str_select($column=false)
    {
        $columns = '*';
        if(!empty($column)&&is_array($column)):
            $columns = $this->str_fill_key($column);
        elseif(is_string($column)):
            $columns = $column;
        endif;
        return 'SELECT '.$columns.' FROM '.$this->str_tablename();
    }
    /**
     * 返回DELETE基本语句
     */
    public function str_delete()
    {
        return 'DELETE FROM '.$this->str_tablename();
    }
    /**
     * 返回UPDATE基本语句
     */
    public function str_update()
    {
        return 'UPDATE '.$this->str_tablename().' SET';
    }
    /**
     * 返回INSERT基本语句
     */
    public function str_insert()
    {
        return 'INSERT INTO '.$this->str_tablename();
    }
    /**
     * 返回ALTER基本语句
     */
    public function str_alter()
    {
        return 'ALTER TABLE '.$this->str_tablename();
    }
    /**
     * 根据数据进行填充SQL标准字段名
     */
    public function str_fill_set($data)
    {
        return implode(',',array_map(fn($v)=>$this->quote($v).'= ? ',$data));
    }
    public function str_fill_key($data)
    {
        if(is_string($data))return $this->quote($data);
        return implode(',',array_map(fn($v)=>$this->quote($v),$data));
    }
    public function str_fill_param($data)
    {
        if(is_array($data))$data = count($data);
        return implode(',',array_fill(0,$data,'?'));
    }
    /**
     * 根据表结构或参数生成字段名VALUES语句
     */
    public function  str_field_values(array $field = array())
    {
        if(empty($field)):
            $field = $this->field();
        endif;
        return ' ('.$this->str_fill_key($field).') VALUES ('.$this->str_fill_param($field,false).');';
    }
    public function  str_field_set(array $field = array())
    {
        if(empty($field)):
            $field = $this->field();
        endif;
        return implode(',',array_map(fn($v)=>$this->str_fill_key($v).' = ?',$field));
    }
    /**
     * 创建数据表语句
     *
     * @return void
     */
    public function str_create(){
        if(isset($this->tableInfo)):
            $query  = 'CREATE TABLE '.$this->str_tablename().' (';
            $arr = array_map(
                fn(string $k,$v):string=>$this->str_fill_key($k)."\t".$v,
                array_keys($this->tableInfo),array_values($this->tableInfo)
            );
            $query .= "\n\t".implode(",\n\t",$arr);
            if(isset($this->tableAlter)):
                $query .= ",\n\t".implode(",\n\t",$this->tableAlter);
            elseif(isset($this->indexkey)):
                $query .= ",\n\t".'PRIMARY KEY('.$this->quote($this->indexkey).')';
            endif;
            $query .= PHP_EOL.') ENGINE='.$this->get_engine();
            $query .= ' DEFAULT CHARSET='.$this->get_charset();
            $query .= ' COLLATE='.$this->get_collate();
            return $query;
        endif;
    }
    public function str_create_as(array $data)
    {
        if(isList($data)):
            return array_map(fn($v)=>$this->str_fullkey($v).' AS '.$this->quote($v),$data);
        else:
            return array_map(fn($key,$v)=>$this->str_fullkey($key).' AS '.$this->quote($v),array_keys($data),array_values($data));
        endif;
    }
    /**
     * 创建关联视图表语句
     *
     * @param array $mode array(关联ID,加入字段,视图后缀)
     * @param array ...$froms array(关联表,加入字段)
     * @return void
     */
    public function str_create_view(array $mode,array ...$froms)
    {
        #CREATE OR REPLACE VIEW `bbs_thread_view` AS SELECT `bbs_thread`.*,`bbs_post`.`message` FROM `bbs_thread`,`bbs_post` WHERE `bbs_thread`.tid = `bbs_post`.tid AND  `bbs_thread`.`firstpid` = `bbs_post`.`pid`
        #CREATE OR REPLACE VIEW `bbs_thread_view` AS SELECT `bbs_thread`.*,`bbs_post`.`message` as `msg` FROM `bbs_thread`,`bbs_post` WHERE `bbs_thread`.tid = `bbs_post`.tid AND  `bbs_thread`.`firstpid` = `bbs_post`.`pid`
        $name = array_shift($mode); 
        $field = array_shift($mode);
        $where = array_shift($mode);
        $fieldlist = array();
        $wherelist = array();
        $selectlist = array();
        $tablelist = array();
        $prelist = array();
        $tablelist[] = $this->str_tablename();
        $prelist[] = $this->str_tablename();
        if(!empty($where)) $wherelist[] = $where;
        if(!empty($field)&&is_array($field)):
            $fieldlist = array_is_list($field)?$field:array_values($field);
            $selectlist[] = $this->str_create_as($field);
        elseif(!empty($field)&&is_string($field)):
            $field = explode(',',$field);
            $fieldlist = $field;
            $selectlist[] = $this->str_create_as($field);
        else:
            $this->field_exec();
            $fieldlist = $this->field();
            $selectlist[] = $this->str_create_as($fieldlist);;
        endif;
        $viewname = $this->get_pre().'view_'.(!empty($name)?$name:$this->table);
        foreach($froms as $num=>$from):
            $fromname = array_shift($from);
            if(empty($fromname))continue;
            $fromfield = array_shift($from);
            $fromwhere = array_shift($from);
            if(is_array($fromname)):
                $fromdb = \Nenge\dbc::app()->table($fromname[0]);
                $tablelist[] = $fromdb->str_tablename().' AS '.$this->quote('table_'.$num);
                $prelist[] = $this->quote('table_'.$num);
            else:
                $fromdb = \Nenge\dbc::app()->table($fromname);
                $tablelist[] = $fromdb->str_tablename();
                $prelist[] = $fromdb->str_tablename();
            endif;
            if(!empty($fromfield)&&is_string($fromfield)):
                $fromfield = explode(',',$fromfield);
            endif;
            if(!empty($fromfield)&&is_array($fromfield)):
                $arr = array();
                foreach($fromfield as $key=>$value):
                    if(is_string($key)):
                        if(in_array($value,$fieldlist))continue;
                        $fieldlist[] = $key;
                        $arr[] = $fromdb->str_fullkey($key).' AS '.$this->quote($value);
                    else:
                        if(in_array($value,$fieldlist))continue;
                        $fieldlist[] = $value;
                        $arr[] = $fromdb->str_fullkey($value).' AS '.$this->quote($value);
                    endif;
                endforeach;
                $selectlist[] = $arr;
            else:
                $fromdb->field_exec();
                $fromfield  = $fromdb->field();
                $newfield   = array();
                foreach($fromfield as $key):
                    if(in_array($key,$fieldlist))continue;
                    $newfield[] = $key;
                    $fieldlist[] = $key;
                endforeach;
                $selectlist[] = $fromdb->str_create_as($newfield);
            endif;
            if(!empty($fromwhere)) $wherelist[] = $fromwhere;
        endforeach;
        $wherestr = implode("\n\t AND ",$wherelist);
        if(!empty($wherelist)):
            $wherestr = preg_replace_callback('/\{(\d+)\}/is',fn($m)=>$prelist[$m[1]],$wherestr);
        endif;
        $query = 'CREATE OR REPLACE VIEW '.$this->str_dbname().'.'.$this->quote($viewname)." (\n\t";
        $query .= implode(",\n\t",array_map(fn($m)=>$this->quote($m),$fieldlist));
        $query .= "\n) AS SELECT \n\t";
        $query .= implode(",\n\t",array_map(fn($v)=>implode(",\n\t",$v),$selectlist));
        $query .= "\nFROM\n\t".implode(',',$tablelist);
        if(!empty($wherestr)):
            $query .= "\nWHERE\n\t".$wherestr.';';
        endif;
        return $query;
    }
    public function value($value)
    {
        $query = $this->str_select().' WHERE '.$this->str_key().' = ?';
        return self::link()->fetch_assoc($query,[$value])?:array();
    }
    public function values($value,$not = false,$select=false)
    {
        $query = ' WHERE '.$this->str_key();
        $param = array();
        if(is_array($value)):
            $value = array_values(array_unique($value));
            $query .= $not?' NOT IN ':' IN ';
            $query .= '('.$this->str_fill_param($value).')';
            $param = $value;
        else:
            $query .= $not ? ' <> ? ':' = ? ';
            $param = [$value];
        endif;
        $result = $this->query($query,$param,$select);
        $data = array();
        if(isset($this->indexkey)):
            foreach($result as $value):
                $key = $value[$this->indexkey];
                $data[$key] = $value;
            endforeach;
            return $data;
        endif;
        return $result;
    }
    /**
     * 统计总数
     */
    public function count(string $query='',$param=array())
    {
        return intval($this->column($query,$param,'count(*)'));
    }
    public function column(string $query='',$param=array(),$select=false)
    {
        if(!empty($query)) $query = $this->parse_query($query);
        return $this->query_column($query,$param,$select);
    }
    /**
     * 最大id
     */
    public function maxid(string $query='',$param=array())
    {
        return $this->column($query,$param,'max('.$this->str_key().')');
    }
    /**
     * 预处理方式SELECT查询并返回所有结果
     */
    public function query($query='',$param=array(),$select=false)
    {
        return self::link()->fetch_all($this->str_select($select).$query,$param);
    }
    public function query_column($query='',$param=array(),$select=false)
    {
        return self::link()->fetch_column($this->str_select($select).$query,$param);
    }
    /**
     * 预处理方式查询并返回带索引关联列
     */
    public function index2column(string $column,string $query='',$param=array())
    {
        if(!empty($query)) $query = $this->parse_query($query);
        $result = $this->query($query,$param)?:array();
        if(isset($this->indexkey)):
            $data = array();
            foreach($result as $value):
                if(isset($value[$column])):
                    $key = $value[$this->indexkey];
                    $data[$key] = $value[$column];
                endif;
            endforeach;
            return $data;
        endif;
        return $result;
    }
    /**
     * 预处理方式查询并返回带索引所有结果
     */
    public function index2array(string $query='',$param=array(),$select=false)
    {
        if(!empty($query)) $query = $this->parse_query($query);
        $result = $this->query($query,$param,$select)?:array();
        if(isset($this->indexkey)):
            $data = array();
            foreach($result as $value):
                $key = $value[$this->indexkey];
                $data[$key] = $value;
            endforeach;
            return $data;
        endif;
        return $result;
    }
    /**
     * 预处理方式查询并返回二维所有结果
     */
    public function group2array(string $groupkey,string $query='',$param=array())
    {
        if(!empty($query)) $query = $this->parse_query($query);
        $result = $this->query($query,$param)?:array();
        if(isset($this->indexkey)):
            $data = array();
            foreach($result as $value):
                if(isset($value[$groupkey])):
                    $key = $value[$this->indexkey];
                    $data[$value[$groupkey]][$key] = $value;
                endif;
            endforeach;
            return $data;
        endif;
        return $result;
    }
    /**
     * 插入一条数据
     */
    public function insert2json(array $data)
    {
        $keys = array_keys($data);
        $values = array_values($data);
        return self::link()->fetch_insert(
            $this->str_insert().$this-> str_field_values($keys),
            $values,
            $this->indexkey
        );
    }
    /**
     * 插入多条数据
     */
    public function insert2array(array $values)
    {
        $query = $this->str_insert().$this-> str_field_values();
        $params = $this->parse_array_param($values);
        return self::link()->fetch_insert_multi($query,$params,$this->indexkey);
    }
    /**
     * 更新数据
     */
    public function update2json(array $data,$key = false,$value=null)
    {
        if(!$key) $key = $this->indexkey;
        $keys = array_keys($data);
        $values = array_values($data);
        $values[] = $value===null?$data[$key]:$value;
        $keystr = $this->str_fill_set($keys);
        return self::link()->fetch_update(
            $this->str_update().' '.$keystr.' WHERE '.$this->quote($key).' = ? ',
            $values
        );

    }
    public function update2array(array $array,$value)
    {
        $array[] = $value;
        return self::link()->fetch_update_multi(
            $this->str_update().' '.$this->str_field_set().' WHERE '.$this->str_key().' = ? ',
            $array
        );
    }
    /**
     * 删除一条符合条件的数据集
     */
    public function query_delete($value,$key=false)
    {
        if(!$key) $key = $this->indexkey;
        return self::link()->fetch_update(
            $this->str_delete().' WHERE '.$this->quote($key).' = ? ',
            [$value]
        );
    }
    /**
     * 执行一条语句
     */
    public function exec(string $query)
    {
        return self::link()->exec($query);
    }
    public function exec_update($query)
    {
        return self::link()->exec($this->str_update().$query);
    }
    public function query_update($query,$param)
    {
        return self::link()->fetch_update($this->str_update().$query,$param);
    }
    /**
     * 执行一条语句
     */
    public function exec_query(string $query,int $mode=1)
    {
        return self::link()->query($query,$mode);
    }
    /**
     * 执行多条语句;
     */
    public function exec_multi(string $query,int $mode = 1)
    {
        $query = $this->parse_query($query);
        return self::link()->multi_query($query,$mode);
    }
    /**
     * 获取数据表引擎
     */
    public function exec_engine()
    {
        return self::link()->fetch_column('SHOW TABLE STATUS FROM '.$this->str_dbname().' WHERE `Name` = ? ',[$this->get_tablename()],1);
    }
    /**
     * 获取MAXID
     */
    public function exec_maxid()
    {
        return self::link()->query($this->str_select('max('.$this->quote($this->indexkey).')').' LIMIT 1',4);
    }
    /**
     * 获取索引信息
     */
    public function exec_index()
    {
        return self::link()->query('SHOW INDEX FROM'.$this->str_tablename());
    }
    public function exec_reset(){
        if($query = $this->str_create()):
            self::act_drop();
            self::link()->query($query);
            if(isset($this->tableData)):
                return $this->insert2array($this->tableData);
            endif;
        endif;
    }
    /**
     * 格式化查询语句
     */
    public function parse_query($query)
    {
        $query = preg_replace('/\{\%table\%\}/i',$this->str_tablename(),$query);
        $query = preg_replace('/\{\%select\%\}/i',$this->str_select(),$query);
        $query = preg_replace('/\{\%insert\%\}/i',$this->str_insert(),$query);
        $query = preg_replace('/\{\%update\%\}/i',$this->str_update(),$query);
        $query = preg_replace('/\{\%delete\%\}/i',$this->str_delete(),$query);
        $query = preg_replace('/\{\%alter\%\}/i',$this->str_alter(),$query);
        $query = preg_replace_callback('/\{(\w[\w\d]+?)\}/is',fn($m)=>$this->quote($m[1]),$query);
        return $query;
    }
    /**
     * 格式化参数
     */
    public function parse_array_param(array $array)
    {
        $newarr = array();
        foreach($array as $item):
            if(isList($item)):
                $newarr[] =  $item;
            else:
                $newarr[] = $this->parse_field($item);
            endif;
        endforeach;
        return $newarr;
    }
    /**
     * 格式化字段参数对应变量
     */
    public function parse_field($json)
    {
        $fields = $this->field_array();
        $newparam = array();
        foreach($fields as $key=>$value):
            $newparam[] = isset($json[$key])?$json[$key]:$value;
        endforeach;
        return $newparam;
    }
    /**
     * 返回数据库链接信息
     */
    public function get_conf()
    {
        return \Nenge\dbc::app()->getConf($this->table);
    }
    /**
     * 获取带前缀的数据表名
     */
    public function get_tablename()
    {
        return $this->get_pre().$this->table;
    }
    public function get_pre()
    {
        if(empty($this->pre)):
            $conf = $this->get_conf();
            $this->pre = empty($conf['pre'])?'':$conf['pre'];
        endif;
        return $this->pre;
    }
    public function get_dbname()
    {
        if(empty($this->dbname)):
            $conf = $this->get_conf();
            $this->dbname = empty($conf['name'])?'':$conf['name'];
        endif;
        return $this->dbname;
    }
    /**
     * 返回数据表的事务驱动引擎
     */
    public function get_engine()
    {
        if(empty($this->tableEngine)):
            $conf = $this->get_conf();
            $this->tableEngine = empty($conf['engine'])?'InnoDB':$conf['engine'];
        endif;
        return $this->tableEngine;
    }
    public function get_charset()
    {
        if(empty($this->tableCharset)):
            $conf = $this->get_conf();
            $this->tableCharset = empty($conf['charset'])?'utf8mb4':$conf['charset'];
        endif;
        return $this->tableCharset;
    }
    public function get_collate()
    {
        if(empty($this->tableCollate)):
            $conf = $this->get_conf();
            $this->tableCollate = empty($conf['collate'])?'utf8mb4_general_ci':$conf['collate'];
        endif;
        return $this->tableCollate;
    }
}