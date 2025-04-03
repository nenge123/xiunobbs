<?php
/*
 * Copyright (C) xiuno.com
 */
defined('MYSQL_DEBUG');
// 0: Production mode; 1: Developer mode; 2: Plugin developement mode;
// 0: 线上模式; 1: 调试模式; 2: 插件开发模式;
!defined('DEBUG') AND define('DEBUG', 1);
!defined('APP_PATH') AND define('APP_PATH',__DIR__.DIRECTORY_SEPARATOR); // __DIR__
!defined('ADMIN_PATH') AND define('ADMIN_PATH', APP_PATH.'admin/');
// !ini_get('zlib.output_compression') AND ob_start('ob_gzhandler');

$conf = (@include APP_PATH.'conf/conf.php') OR exit('<script>window.location="install/"</script>');

// 兼容 4.0.3 的配置文件	
!isset($conf['user_create_on']) AND $conf['user_create_on'] = 1;
$conf['version'] = '4.1.0';		// 定义版本号！避免手工修改 conf/conf.php
include APP_PATH.'xiunophp/xiunophp.php';
// 测试数据库连接 / try to connect database
//db_connect() OR exit($errstr);

include APP_PATH.'model/plugin.func.php';
include _include(APP_PATH.'model.inc.php');
include _include(APP_PATH.'index.inc.php');

