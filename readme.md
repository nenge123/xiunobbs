流程
=====
- `Nenge\APP::app()` 启动
- 读取config.inc.php,初始化`DB::app($conf)`
- 生成或者读取 settings.php 含有settings表所有数据,板块列表,板块权限,版本版主,用户组列表,运行路径,网站路径,邮箱列表,默认路由列表
- 初始化变量 设置cookie http only,分析URL生成路由参数,加载语言,读取插件信息,设置时区,读取COOKIES,加载登录信息.


## 模块手册
- [插件模块](md/plugin.md)
- [数据库类处理](md/db.md)
- [模板类模块](md/template.md)
- [Nenge\APP变量说明](md/myapp_var.md)
- [Nenge\APP类说明](md/myapp_class.md)

> 前台

- [Javascript模块手册](md/js-hack.md)


## 使用资源
- JavaScript
    - tinymce.zip 编辑器
    > https://tiny.cloud/
    - webp_encoder.zip gif to webp  
    > https://github.com/xiaozhuai/webp_encoder/ 
    - modernGif.js gif分解出每帧
    > https://github.com/qq15725/modern-gif 
    - zip.min.js zip解压压缩
    > https://github.com/gildas-lormeau/zip.js/
    - extract7z.zip 7zip解压
    - libunrar.min.zip rar4解压

- PHP类
    - PHPMailer 邮件发送
    > https://github.com/PHPMailer/PHPMailer  
    - ScssPhp scss转css
    > https://github.com/scssphp/scssphp 