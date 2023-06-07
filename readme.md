# 流程

- `Nenge\APP::app()` 启动
- 读取config.inc.php,初始化`DB::app($conf)`
- 生成或者读取 settings.php 含有settings表所有数据,板块列表,板块权限,版本版主,用户组列表,运行路径,网站路径,邮箱列表,默认路由列表
- 初始化变量 设置cookie http only,分析URL生成路由参数,加载语言,读取插件信息,设置时区,读取COOKIES,加载登录信息.

## $myapp->data 备忘录

## settings 设置

- `router_replace` 数组 路由名替换 `array('forum'=>'abc')` `forum.html->abc.html`

- `style_name` 字符 自定义主题名字

## template 模板

- 目录分析 `$myapp->str_path($name,'template')` 

- 读取模板 `$myapp->str_path($name)` name格式`abc`||`plugin:abc` 前者可被替换,后者强制使用插件目录.替换模式由插件信息的`template`属性优先替换,否则由主题替换.查找失败均会返回默认目录.

- `templae->get_path_file($path,$file,$allpath)` 按照`htm,php`等文件格式查找模板,php格式主要目的是防止被下载.

# 插件

- 启动插件需要含有app.json
```json
{
    "router":"admin", #定义自定义路由名,多个之间用逗号隔开
    "class":"admin" #定义加载类,对应插件目录下admin.class.php
    "template":"", #模板替换,优先级高于主题
    "require":"", #插件前置,如果没有指定前置插件,那么这个插件不会启用
}
```

- 插件缓存
    >网站出现问题可以直接删除,只有删除settings.php才会重新生成  

    - `list` 启用的插件列表
        - `dir_include` 判断是否含有路由文件hook导入内容
            > 如果文件为php后缀,`<?php  ... ?>`必须完整
        - `dir_hook` 判断是否含有模板文件hook导入内容
            > 如果文件为php后缀,应用 `return ;`返回要插入的代码.使用此方法可以避免代码泄露
        - `dir_template` 是否含有模板目录
        - `dir_css` 是否含有css样式目录
        - `dir_lang` 是否含有语言目录,会加载语言
    - `router` 路由替换列表
    - `template` 模板替换
    - `method` 插件类方法汇总
        - `$plugin_class::template()`  模板内容转换.



# 类接口

## `Nenge\APP` 核心类 初始化属性

- `::app()` 尝试读取congfig.inc.php初始化运行

- `write_settings()` 初始化写入并读取网站配置信息

- `router($name)` 查找并返回路由文件

- `template($name)` 查找并返回模板文件

- `str_encrypt($txt)` 加密文本

- `str_decrypt($txt)` 解密文本

- `str_hash($str, $algo = PASSWORD_DEFAULT)` 单向加密,不可逆

- `str_verify($str, $hash)` 验证密码是否与密文匹配

- `output_debug()` 打印查询语句,文件加载情况

- `url($router, $param = '', $clear = true)` 输出格式化地址,第三个参数false时,尾部参数会根据当前地址queryString覆盖上去.

- `avatar($user)` 返回用户头像

- `allowforum()` 返回当前用户有访问权的板块

- `time_human($timesize)` 返回人性化时间


## `Nenge\template` 模板处理类

- `parse` 正则匹配处理,调用插件类中的`::template($template)`

    - `{url abc}`=`/?abc.html` 地址,可以两个参数,空格隔开,`{url abc k=1}`=`/?abc.html?k=1`,调用函数`$myapp->url()`

    - 以`.scss`的样式文件会被转化为css,需要使用admin插件中的SCSS,那么格式为`href="admin:style.scss"`,否则会读取默认目录.

    - `{subtemplate xxx}` 导入模板,直接附加上去,一些循环语句中很有用

    - `{template xxx}` 引用模板,不直接附加,而是通过include加载,会产生独立缓存文件.调用函数`$myapp->template()`

    - `{hook xxx}` 插入描点.

    - `{time $timeline}` 年月日时分秒格式格式化时间

    - `{timehuman $timeline}` 人性化时间显示,例如几天前

    - `{echo $uid}` 输出变量

    - `{eval $uid}` 自定义语句

    - `{if $uid}{else}{/if}` if..else..

    - `{loop $data $k $v} {/loop}` = `foreach($data as $k=>$v){ ... }`

    - `{$uid}` 等价于  `{echo $uid}`

    - `{lang xxx}` 显示语言包,等价于 `$language['xxx']`



- `get_path_file($path,$file,$base=false)` 匹配模板文件,从htm格式匹配到php格式,如果查找失败尝试查找默认模板

## `Nenge\DB` MYSQL接口 涉及用户输入务必使用预处理方式,不应该语句中包含数据!

- `Nenge\db_mysql` 驱动底层  
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

- `Nenge\DB` 接口层

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

- `Nenge\table\base` 数据库table类基础
    ```php
        namespace Nenge\table;
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