SCSS
=====
本站使用scssphp作为解析工具,具体github:scssphp/scssphp

### 特定函数:**SitePath** ###
>`SitePath('文件路径','目录名')` 参考 `$myapp->data['site']`  

返回一个绝对路径  
`src: url(SitePath("webfont.woff2","fonts")) format("woff2");`  
输出 `src:url("/assets/fonts/fwebfont.woff2") format("woff2");` 


### 默认变量 ###
> 当风格定义了变量`$myapp->style['var']`  
> 如 `$myapp->style['var']['abc']='5px';`  
> 那么对应可以调用 `$abc:5px;`  

变量名尽可能带前缀  
不要重复数学变量,例如自适应设置`$mobilewidth:768px;$mobilewidth2:767px;`  
正确使用应该只定义`768px`,若需要用到`767px`应该使用`#{$mobilewidth - 1}`  

***特别注意:*** `css3:max|min`会与`scss`冲突,避免冲突用大写`MAX|MIN`.  
***书写原则:*** 尽可能用`border:#{$var};` 而不是 `border:$var`;  
**下翻查看一些常用scss语法**

### 导入规则 ###
>`@import "wbox.scss";`  
> 默认情况下只导入 `$myapp->data['path']['scss']` 或者 `$myapp->data['path']['css']`的文件.  
> 如果要导入本目录,或者其他插件目录,需要增加插件名前缀`@import "abc:wbox.scss`
> 此时导入文件变为 `$myapp->data['path']['plugin'].'abc/wbox.scss'`  

此导入规则可被插件类 `plugin::find_scss`拦截  
作用等价于 `$myapp->get_scss_path($scss)`(**禁止find_scss调用此函数**)  


### 代码复用:@mixin ###
```scss
@mixin abc{
    font-size:1px;
}
@mixin abc2($size:2px){
    /* $size 默认值 2px */
    font-size:#{$size};
}
@mixin abc3($size){
    /* 条件判断 not是代表不存在,(=='') 是表示是空值 */
    @if not $size or $size =='' {
        $size: 6px;
    }
    font-size:#{$size};
    /* 是代码附加内容 */
    @content;
}
#a{
    @include abc;
}
#b{
    @include abc2(4px);
}
#c{
    @include abc2;
}
#d{
    @include abc3{
        border:1px solid #000;
    };
}
//输出 #a{font-size:1px}
//输出 #b{font-size:4px}
//输出 #c{font-size:2px}
//输出 #d{font-size:6px;border:1px solid #000;}
```

### 函数:@function ###
```scss
@function space_width($width) {
    @return unquote("calc(100% - "+#{$width}+")");
}
```