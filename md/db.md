说明
====
> MYSQL接口 涉及用户输入务必使用预处理方式,不应该语句中包含输入数据!  
> 每个表建立`table_xxx`专属查询,处理.调用方式`DB::t('user')->uids(1)`,`DB::t('thread')->page_by_fid()`等.


## `Nenge\db_mysql` 驱动底层  

- `prepare($sql,$param,$list=false)` <b>单条语句</b>预处理,涉及用户输入输出必须用这个,插入/更新值用`?`,例如`tid=?`,那么对应`$param=array(1)`,参数按照问号顺序.例如相同语句插入多条数据用`array(array(1),array(2))`

- `result($sth, $method = 0, $type = 1)` 返回查询结果,$sth是一个查询对象或者`array($sql,$param)`,`$method`:0是返回多行,1返回单行,2返回单行某列数据,`$type`:当返回行时,1&2缺点返回数据是否含有字段名,返回列是返回第几列(0开始)的值.

- `multi_query($sql, $method = 0, $type = 1)` 多条语句查询

- `query($sql, $method = 0, $type = 1`) 单条语句查询

- `exec($sql)` 返回查询后影响行数和insert id

- `result_fetch($sql, $param = array(), $type = 1)` 简化后的单行查询

- `result_all($sql, $param = array(), $type = 1)` 简化后的多行查询

- `result_first($sql, $param = array(), $index = 0)` 简化后的单行单列查询

- `result_query($sql, $param = array())` 返回执行后的行影响`array('rpws'=>,'lastid'=>,'sql'=>)`

- `update($sql, $param)` 更新数据

- `insert($sql, $param, $list = false)` 插入数据,如果`$list`为true,一维的插入数据当成二维来插入,慎用.

##  `Nenge\DB` 接口层

- `:: app($conf)` 使用前初始化,`$conf`数据库账号配置信息

- `:: t($table)` 静态接口,加载tablel类

- `prepare_exec($query)` 根据参数生成标准预处理语句以及数值

- `:: FetchOne($table, $where = '', $query = array())` 预处理查询指定表单行数据

- `:: FetchAll($table, $where = '', $query = array())` 预处理查询指定表多行数据

- `:: FetchColumn($table, $where = '', $query = array())` 预处理查询指定表单行单列数据

- `:: update($table, $data, $where = '', $query = array())` 预处理更新指定表数据

- `:: insert($table, $data, $update = false, $query = array())` 预处理插入新指定表数据,`$update`:是否存在数据(唯一字段)就变成更新数据

- `:: Rows($table)` 预处理查询指定表,分析行数

- `:: TableField($table = '', $quote = true)` 预处理查询单个或者多个表的字段名.

- `:: DBField()` 预处理查询当前数据库中所有表.

- `:: getSql()` 获取所有数据库查询的记录以及耗时.

- `:: mquery_table($table)` 查询多个表的所有数据

- `:: query($sql, $method = 0, $type = 1)` 单行数据查询

- `:: mquery($sql, $method = 0, $type = 1)` 多行数据查询

- `:: getLink($table = false)` 返回驱动底层类对象

## `table\base` 数据库table类基础
```php
    namespace table;
    use Nenge\DB;
    class table_attach extends base{
        function __construct()
        {
            $this->table = 'attach';
            $this->indexkey = 'aid';
        }
    }
    #调用方式 $result = DB::t('attach')->all();
```

- `connect()` 返回驱动底层类

- `tablename()` 带前缀的表名

- `quote_table($table='',$dbname='')` 参数留空:带反引号的标准表名+表名数据库前缀,否则根据参数给参数反引号连接起来.

- `fetch($where = '', $query = array())` 预处理查询单行数据.

- `all($where = '', $query = array())` 预处理查询多行数据.

- `column($where = '', $query = array())` 预处理查询单行单列数据.

- `field()` 获取表所有字段名.

- `fieldAttr()`返回字段属性.

- `insert($data, $update = false, $query = array())` 预处理插入数据.

- `update($data, $where = '', $query = array())` 预处理更新数据.

- `rows($where = '', $key = false)` 查询行数.

- `rows_by_exp($where = '', $key = false)` 分析方式获取行数.

- `rows_by_table()` 非增表的行数.

- `index()` 预处理查询主键.
- `rand($limit = 1, $where = '', $fetchmethod = 0, $fetchmode = 0)` 排序方式随机数据.

- `rand_by_id($where = '', $indexkey = false, $fetchmethod = 1, $fetchmode = 0)` 随机一条数据.