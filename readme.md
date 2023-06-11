# 流程

- `Nenge\APP::app()` 启动
- 读取config.inc.php,初始化`DB::app($conf)`
- 生成或者读取 settings.php 含有settings表所有数据,板块列表,板块权限,版本版主,用户组列表,运行路径,网站路径,邮箱列表,默认路由列表
- 初始化变量 设置cookie http only,分析URL生成路由参数,加载语言,读取插件信息,设置时区,读取COOKIES,加载登录信息.

# 模块手册
- [数据库处理](db.md)
- [插件模块](plugin.md)
- [模板模块](template.md)

# $myapp->data 备忘

## settings 设置

- `router_replace` 数组 路由名替换 `array('forum'=>'abc')` `forum.html->abc.html`

- `style_name` 字符 自定义主题名字

# 类接口  `Nenge\APP` 核心类 初始化属性

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
