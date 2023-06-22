说明
====
> 启动插件需要含有app.json 替换参数/加载类/js  不允许有斜杠,多个用逗号隔开
```json
{
    "router":"admin", #定义自定义路由名,多个之间用逗号隔开 替换路由
    "class":"admin" #定义加载类,
    "template":"", #模板替换,优先级高于主题
    "require":"", #插件前置,如果没有指定前置插件,那么这个插件不会启用
    "css":"",#替换scss
    "js":"",#默认加载js
}
```

## 插件缓存
> 网站出现问题可以直接删除,只有删除settings.php才会重新生成  

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
- `method` 插件类方法汇总,参考插件类

## 插件类
> 如果`app.json->class`为true则是`plugin\xxx->xxx.class.php`,多个类逗号隔开,`a->plugin\xxx_a->a.class.php` 
```php
<?php
#app.json中class设置为true plugin/xxx/xxx.class.php
namespace plugin\xxx;
class xxx{
    public function template($template,$path,$file)
    {
        return '<!-- plugin_test->template('.$file.') -->'.$template;
    }
}
?>
<?php
#app.json中class设置为"test,test2" plugin/xxx/test.class.php plugin/xxx/test2.class.php
namespace plugin\xxx;
class test{
    public function template($template,$path,$file)
    {
        return '<!-- plugin_test->template('.$file.') -->'.$template;
    }
}
?>

<?php
#自定义类 使用 <!--{echp plugin\xxx\abc::test()}>
#xxx为插件目录 abc为xxx目录下的abc.class.php
namespace plugin\xxx;
class abc{
    public static function test($template,$path,$file)
    {
        return 'test';
    }
}
?>

```
- APP->plugin_method_call
> 调用方式  
```php
$myapp->plugin_method_call(
    'template', #method
    function($plugin_method) use (&$template){ #$plugin_method callable
		$template=call_user_func($plugin_method,$template,$this->path, $this->file);
    }
);
#or
foreach($myapp->plugin_method_call('template') as $plugin_method){
	$template=call_user_func($plugin_method,$template,$this->path, $this->file);
}


```
- APP->plugin_method_filter
> 单纯处理单一变量
```php
$postlist = $myapp->plugin_method_filter('postlist',$postlist);
```