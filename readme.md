流程
=====
- `Nenge\APP::app()` 启动
- 读取config.inc.php,初始化`DB::app($conf)`
- 生成或者读取 settings.php 含有settings表所有数据,板块列表,板块权限,版本版主,用户组列表,运行路径,网站路径,邮箱列表,默认路由列表
- 初始化变量 设置cookie http only,分析URL生成路由参数,加载语言,读取插件信息,设置时区,读取COOKIES,加载登录信息.


## 模块手册
- [插件模块](plugin.md)
- [数据库类处理](db.md)
- [模板类模块](template.md)
- [Nenge\APP变量说明](myapp_var.md)
- [Nenge\APP类说明](myapp_class.md)
