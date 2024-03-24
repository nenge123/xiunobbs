变量
=====
> 调用方式 `$myapp->data['settings']`,模板中可简化为`$myapp['settings']`  


## $myapp->data 备忘

- settings 设置
    - `router_replace` 数组 路由名替换 `array('forum'=>'abc')` `forum.html->abc.html`

    - `style_name` 字符 自定义主题名字
- forumlist 板块列表
- grouplist 用户组列表
- forum_access 板块权限
- forumnames 板块名列表
- ver 缓存生成时间
- path 预定义路径列表
    - web 路径:父目录
    - root 路径:网站目录
    - assets 路径:网站/assets文件夹
    - cache 路径:网站/cache文件夹
    - data 路径:网站/cache/data文件夹
    - _css 路径:/cache/css/
    - _router 路径:/cache/router/
    - _template 路径:/cache/template/
    - lang 路径:/assets/lang/
    - class 路径:/assets/class/
    - template 路径:/assets/template/
    - router 路径:/assets/router/
    - css 路径:/assets/css/
    - plugin 路径:/plugin/
    - upload 路径:/upload/
    - attach 路径:/upload/attach/
    - avatar 路径:/upload/avatar/
    - forum 路径:/upload/forum/
    - tmp 路径:/upload/tmp/
- site 预定义网站路径列表(绝对地址)
    - root 网站目录
    - assets 网站/assets目录
    - js 网站/assets/js/目录
    - css 网站/assets/css/目录
    - fonts 网站/assets/fonts/目录
    - lang 网站/assets/lang/目录
    - images 网站/assets/images/目录
    - template 网站/assets/template/目录
    - cache 网站/cache/目录
    - data 网站/cache/data/目录
    - _css 网站/cache/css/目录
    - _router 网站/cache/router/目录
    - _template 网站/cache/template/目录
    - plugin 网站/cache/plugin/目录
    - upload 网站/cache/upload/目录
    - attach 网站/cache/attach/目录
    - avatar 网站/cache/avatar/目录
    - forum 网站/cache/forum/目录


## $myapp->conf 配置文件

- lang 语言 zh-cn
- cookie_prefix
- cookie_domain
- cookie_path 
- encrypt_method 加密方式 AES-256-CBC
- encrypt_key' 32位加密字符串,一旦设置不可随意更改,否则无法解密
- debug 布尔值,调试输出,模板总是实时刷新
