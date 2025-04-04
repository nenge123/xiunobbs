<?php

/**
 * @author N <m@nenge.net>
 * 起始页
 * 退出登录
 */
!defined('ADMIN_PATH') and exit('Access Denied.');
MyApp::setValue('title', MyApp::Lang('logout_successfully'));
// hook admin_index_logout_start.php
MyApp::cookies('admin_token', '');
// hook admin_token_clean_start.php
#返回首页
MyApp::message(0, MyApp::Lang('logout_successfully'), ['url'=>MyApp::topurl('index')]);
