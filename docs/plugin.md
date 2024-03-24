插件说明
=======
### app.json ###
> 插件信息标识,  
> 插件标识原则上`作者名-插件名`,但是不要同时出现`作者名_插件名`因为两者会出现冲突.两者命名方式应该只取其一

- name `字符串` 插件名
- available `布尔值`  此插件可用
    > 如果没有此信息,插件会成为隐式插件,仍旧可以主动引用  
    > 例如一个风格插件控制中心,控制不同风格
- require 字符串 前置插件,多个插件逗号隔开
    > 如果没有前置插件,此插件会被忽略,注意隐式插件不属于检测范围
- class `布尔值` 启用插件类 必须含有主文件 class/common.class.php
    >且命名空间为 namespace plugin\插件目录名;  
- lang 启用插件语言包,附加原则,不会覆盖
    > 当插件语言包与程序内置语言包相同才会生效
- style `布尔值` 风格插件
    > 如果隐式风格请勿启用,此项启用多个只会生效一个,隐式风格插件发布时应把生成的变量文件复制到文档目录,并且命名为data.php,方便插件调用基础值
- template `布尔值` 模板文件替换
    > 替换默认模板, 优先级低于插件类`class->find_template()`  
    > 设置index 例如首页index,会被替换为插件中的template/index.htm
- router `布尔值` 路由替换
    > 优先级低于插件类


### 插件类 ###
> 文件必须是 /plugin/插件名/class/common.class.php  
> 若需要启用插件其他类`$myapp->get_plugin_class('插件名\\类名')=> /plugin/插件名/class/类名.class.php`
> `$myapp->toReplace('arrray_threadlist',$threadlist,'index',$fid)`  
> 根据前缀实现不同方法

- get_ 必须有返还值,唯一,同名函数多个插件只有一个插件生效,常用于替换路由文件的SQL查询
- replace_string_ replace_array_ 必须有返还值,且根据类型返回相同类型,过滤结果
    > 使用时至少有一个参数,如 `$data = replace_arrray_threadlist($data,...其他可选固定参数)`
- find_ 获取非空返回值立即返回并终止后续操作
- echo_ 立即输出字符
- str_ 返回联合的字符串


### 模板优先级 ###
> `$myapp->template('abc');` 返回一个已解析php文件
- 第一优先级:`pluginName:abc`
    > 总是读取 `plugin/pluginName/template/abc.htm`
- 第二优先级
    > $class->find_template()
- 第三优先级 插件风格替换
    > 注意!插件模板内部调用插件模板应该带上 `pluginName` 前缀.否则仍旧经历判断.
- 第四优先级 `$myapp->plugin['include']`
    > 此优先级不会替换二级目录  
    > 直接返回已解析的html模板  
    > 必须注意!调用二级目录文件应该用 `include __DIR__.'/abc_k.php'`
- 第五优先级
    >  `此优先级不会替换二级目录`  
    > 替换`abc`模板路径为  
    > `plugin/pluginName/template/abc.htm`  
    > 必须注意,如果需要调用二级目录文件,请使用插件名前缀.

### scss导入规则 ###
> 导入带前缀的 如 `@import "abc:wbox.scss";`  
> 将会读取 `plugin/abc/css/wbox.scss`  
> 如果没有前缀永远是 `assets/app/scss/wbox.scss` 
> 注意此规则仍可以被插件类放大 `find_scss()` 拦截,返回值必须是`array(文件路径,缓存路径,前端访问地址)`

### 数据库扩展原则 ###

如果需要扩展用户积分等功能.
- 创建扩展表 `bbs_user_field`,扩展表原则上应该添加默认值
    >创建前先通过`$myapp->data['database']` 判断是否存在  
    >如果表存在则通过`$myapp->t('user_field')->field()`判断字段是否存在.
- 建立视图表`bbs_view_userinfo`
    ```php
    $query = $myapp->t('user')->str_create_view(array('userinfo'),array('user_field',false,'{0}.`uid` = {1}.`uid`'));
    ```
- 拦截查询
    ```php
    $userlist = $myapp->toFind('userdata',$uids);
    ```
- 拦截查询
    ```php
    function find_userdata(){
        $table = $myapp->t('view_userinfo'); #若未定义文件仍旧可以匿名类
        $table->table = 'view_userinfo';#匿名类配置 设置表名
        $table->indexkey = 'uid';#匿名类配置 设置主键
        return $table->values($uids)
    }
    ```
