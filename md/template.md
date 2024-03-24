说明
=====
> 模板标记处理.调用方式 `include $myapp->template('index');`

- `->parse()` 正则匹配处理,调用插件类中的`::template($template)`

    - `{{ url('abc') }}`=`/?abc.html` 地址,可以两个参数,空格隔开,`{{ url('abc','k=1') }}`=`/?abc.html?k=1`,调用函数`$myapp->url()`

    - 以`.scss`的样式文件会被转化为css,需要使用admin插件中的SCSS,那么格式为`href="admin:style.scss"`,否则会读取默认目录.

    - `{subtemplate xxx}` 导入模板,直接附加上去,一些循环语句中很有用

    - `{template xxx}` 引用模板,不直接附加,而是通过include加载,会产生独立缓存文件.调用函数`$myapp->template(xxx)`

    - `{hook xxx}` 插入描点.调用`$myapp->plugin_call_hook('xxx')->plugin_class::echo_xxx()`

    - `{hook('xxx',$xxx)}` 插入描点.调用`$myapp->plugin_call_hook('xxx',$xxx)->plugin_class::echo_xxx($xxx)`

    - `{time $timeline}` 年月日时分秒格式格式化时间

    - `{timehuman $timeline}` 人性化时间显示,例如几天前

    - `{echo $uid}` 输出变量
    
    - `{echovar $uid}` 输出变量,附加`!empty()`判断

    - `{eval $uid}` 自定义语句

    - `{if $uid}{else}{/if}` if..else..

    - `{ifvar $uid}{else}{/if}` if..else.. 附加`!empty()`判断

    - `{loop $data $k $v} {/loop}` = `foreach($data as $k=>$v){ ... }`

    - `{$uid}` 等价于  `{echo $uid}`

    - `{lang xxx}` 显示语言包,调用 `$language['xxx']`,预留单网站多语言支持,如需直接显示文字可以修改模板`function fn_lang($param1)`-`'<?php echo $language['. $param1 .']; ?>'=>$language[$param1]`

