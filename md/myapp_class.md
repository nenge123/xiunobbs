Nenge\APP类
=====

- `::app()` 尝试读取congfig.inc.php初始化运行

- `write_settings()` 初始化写入并读取网站配置信息

- `router($name)` 查找并返回路由文件

- `template($name)` 查找并返回模板文件

- `str_encrypt($txt)` 加密文本

- `str_decrypt($txt)` 解密文本

- `str_hash($str, $algo = PASSWORD_DEFAULT)` 单向加密,不可逆

- `str_verify($str, $hash)` 验证密码是否与密文匹配

- `debug()` 打印查询语句,文件加载情况

- `url($router, $param = '', $clear = true)` 输出格式化地址,第三个参数false时,尾部参数会根据当前地址queryString覆盖上去.

- `avatar($user)` 返回用户头像

- `allowforum()` 返回当前用户有访问权的板块

- `time_human($timesize)` 返回人性化时间

- `session_verify()` 敏感操作 验证登录状态,进行一次用户查询,获取当前用户所有数据