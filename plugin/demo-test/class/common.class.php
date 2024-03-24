<?php
/**
 * 插件类核心类
 * 如果插件名含有 "-" 会被替换成 '_'
 * 前缀说明
 *      replace_ 必须含有与传入值相同类型返回值,会循环执行每个插件的替换
 *          replace_string_  传入第一个参数为字符
 *          replace_array_   传入第一个参数为数组
 *          replace_object_  传入第一个参数为对象
 */
namespace plugin\demo_test;
class common{
    #数据初始化后立即调用
    public function common()
    {
           
    }
    public function thread_kk()
    {
        # code...
    }
    #public function common_router() 启动默认路由前,被插件拦截的路由不会调用

    #public function common_forum_footer() 当页面由forum/footer.htm结束时调用
    #public function common_forum_header() 当页面由form/footer.htm开始时调用

    #public function read_forum_thread_list():array; #获取主题列表 包含置顶主题
    #public function set_forum_thread_list(&$threadlist):array; #对主题列表设置,注意必须设置第一参数为引用!

    #public function read_onlinelist():array; #在线列表
    #public function read_onlinelist():array; #在线列表


    #public function find_style():array; #获取风格
    #返回一个已解析的PHP文件,非 xx.htm 未解析文件!
    #此函数禁止调用: $myapp->template($templateName)
    #此函数禁止调用: $myapp->read_template_path($templateName)
    #public function find_template($templateName):string;#获取模板

    
    #拦截 scss内部 @import '$scss';
    #public function find_scss_path($scss):string;#scss地址解析
    #拦截 templatea <link href="$scss">; $myapp->scss($scss);
    #因此返回一个web可访问地址 xxx.css
    #public function find_scss_link($scss):string;#拦截scss地址解析


}