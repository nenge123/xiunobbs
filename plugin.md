说明
====
> 启动插件需要含有app.json
```json
{
    "router":"admin", #定义自定义路由名,多个之间用逗号隔开
    "class":"admin" #定义加载类,对应插件目录下admin.class.php
    "template":"", #模板替换,优先级高于主题
    "require":"", #插件前置,如果没有指定前置插件,那么这个插件不会启用
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
> 用于数据过滤等处理,类名以`plugin_xxx`命名,文件名对应`xxx.class.php`
```php
<?php
namespace Nenge\plugin;
class plugin_test extends base{
    public function template($template,$path,$file)
    {
        return '<!-- plugin_test->template('.$file.') -->'.$template;
    }
}
?>
```
> 调用方式  
```php
$myapp->plugin_class_call(
    'template', #method
    function($plugin_method) use (&$template){ #$plugin_method callable
		$template=call_user_func($plugin_method,$template,$this->path, $this->file);
    }
);
#or
foreach($myapp->plugin_class_call('template') as $plugin_method){
	$template=call_user_func($plugin_method,$template,$this->path, $this->file);
}


```