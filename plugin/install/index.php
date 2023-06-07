<?php
defined('XIUNO') || die('return to <a href="">Home</a>');

use Nenge\APP;
use Nenge\DB;
use Nenge\db_mysqli;

if (empty($myapp->data)) {
    $myapp->data = $myapp->init_path();
    $router = $myapp->init_router_var();
    if (empty($router)) {
        $router = array(
            0 => 'install',
            1 => 'index',
            'install' => 'index'
        );
    }
    $myapp->data['router'] = $router;
    $myapp->conf['debug'] = !0;
    $router_name = $router[0];
    $router_value = $router[1];
}
$lang_data = array(
    'zh-cn',
    'zh-tw',
    'en'
);
$version = '5.0.0';
$install_path = $myapp->data['path']['plugin'] . 'install\\';
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'zh-cn';
if (empty($myapp->language) || $lang != 'zh-cn') {
    if (is_file($myapp->data['path']['lang'] . $lang . '.php')) $myapp->language = include $myapp->data['path']['lang'] . $lang . '.php';
    else $myapp->language = include $myapp->data['path']['lang'] . 'zh-cn.php';
}
if (is_file($install_path . 'lang\\' . $lang . '.php')) $myapp->language += include $install_path . 'lang\\' . $lang . '.php';
else $myapp->language += include $install_path . 'lang\\zh-cn.php';
$data = $myapp->data;
if (!is_dir($data['path']['cache'])) {
    $myapp->mkdir($data['path']['cache']);
    $arr = ['data', 'css', 'template', 'router', 'class'];
    foreach ($arr as $k => $v) {
        if (!is_dir($data['path']['cache'] . $v)) {
            $myapp->mkdir($data['path']['cache'] . $v);
        }
    }
}
if (!is_dir($data['path']['upload'])) {
    $myapp->mkdir($data['path']['upload']);
    $arr = ['attach', 'avatar', 'forum', 'tmp'];
    foreach ($arr as $k => $v) {
        if (!is_dir($data['path']['upload'] . $v)) {
            $myapp->mkdir($data['path']['upload'] . $v);
        }
    }
}
if (!is_dir($data['path']['plugin'])) {
    $myapp->mkdir($data['path']['plugin']);
}
$language = Nenge\language::app();
$myapp->data['title'] = $language['install_title'];
if (empty($router_value) || !in_array($router_value, array('index', 'env', 'db', 'license'))) $router_value = 'index';
if ($router_value == 'env') {
    if (!is_dir($myapp->data['path']['upload'])) {
        $myapp->mkdir($myapp->data['path']['upload']);
    }
} elseif ($router_value == 'db') {
    if (!empty($_POST)) {
        $dbconf = array();
        $connect_error = !0;
        foreach (array('host', 'user', 'pw', 'pre', 'charset') as $v) {
            $dbconf[$v] = trim($_POST[$v]);
        }
        try {
            class_exists('Nenge\\DB');
            $link = new db_mysqli($dbconf);
            $result = $link->result_all('SHOW DATABASES;');
            $connect_error = !1;
            $havedb = !1;
            foreach ($result as $v) {
                if ($v['Database'] == trim($_POST['dbname'])) {
                    $havedb = !0;
                }
            }
            $dbconf['dbname'] = trim($_POST['dbname']);
            if (!$havedb) {
                $create_error = !0;
                //$link->query('CREATE DATABASE IF NOT EXISTS ' . DB::quote($dbconf['dbname']) . ';');
            }
            $link->exec('USE ' . DB::quote($dbconf['dbname']) . ';');
            $create_error = !1;
            $havedb = !1;
            $result = $link->result(array('SELECT `TABLE_NAME` FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = ? ;', array($dbconf['dbname'])));
            $dbtable = [];
            foreach ($result as $v) {
                $dbtable[] = $v['TABLE_NAME'];
            }
            $cover_error  = !1;
            if (empty($_POST['cover']) && in_array($dbconf['pre'] . 'settings', $dbtable)) {
                $cover_error = !0;
            } else {
                #install
            }
        } catch (Exception $e) {
        }
    } else {
        if (!empty($myapp->conf[1])) $dbconf = $myapp->conf[1];
    }
}
#print_r($myapp->data);
include $myapp->template('install:' . $router_value);
