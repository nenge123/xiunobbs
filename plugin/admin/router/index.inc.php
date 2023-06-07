<?php
defined('XIUNO')||die('return to <a href="">Home</a>');
#$dbinfo = $myapp->DB('support');print_r($dbinfo);exit;
include $myapp->getTemplate('admin:index');
/*
use Nenge\APP;

$myapp['title']= $language['admin_index'];
$stat = $myapp->DB('FetchTableRows','thread,post,user,attach,online');
$stat['disk_free_space'] = function_exists('disk_free_space') ? Nenge\xnfunc::humansize(disk_free_space($myapp->path->root)) : $language['unknown'];
$dbinfo = APP::DB('support');
if(empty($dbinfo['version']))$dbinfo['version'] =  APP::DB('FormatColumn','SELECT VERSION();');
$stat['allow_url_fopen'] = $language[ini_get('allow_url_fopen') ? 'yes':'no'];
$stat['safe_mode'] = $language[ini_get('safe_mode') ? 'yes':'no'];
$stat['serverIP'] = empty($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:$_SERVER['REMOTE_ADDR'];
#print_r($dbinfo);exit;
include $myapp->getTemplate('admin/index');
*/