<?php
defined('WEBROOT') || die('return to <a href="">Home</a>');
use Nenge\APP;
$lang_data = array(
    'zh-CN',
    'zh-TW',
    'en'
);
$query = explode('/',trim($_SERVER['QUERY_STRING'],'/'));
$lang = array_shift($query)?:'zh-CN';
$mode = array_shift($query)?:'index';
$version = '1.0.0';
$myapp->language = include(__DIR__.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.$lang.'.php');
$data = $myapp->data;
$language = Nenge\language::app();
$myapp->data['title'] = $language['install_title'];
if (empty($mode) || !in_array($mode, array('index', 'env', 'db', 'license'))) $mode = 'index';
if ($mode == 'env') {
    if (!is_dir($myapp->data['path']['upload'])) {
        $myapp->mkdir($myapp->data['path']['upload']);
    }
} elseif ($mode == 'db') {
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
include $myapp->template('install:' . $mode);
