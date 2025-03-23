<?php
/*
	Xiuno BBS 4.0 用户组升级增强版
	插件由xiuno非官方提供：http://www.xiuno.top/
*/
!defined('DEBUG') AND exit('Access Denied.');
if($method == 'GET') {
	$kv = setting_get('sg_group');
	$input = array();
	$input['up_group'] = form_select('up_group',array('1'=>lang('sg_credits'), '2'=>lang('sg_up_group2'), '3'=>lang('sg_up_group3')), $kv['up_group']);
	$input['create_credits'] = form_text('create_credits', $kv['create_credits']);
	$input['post_credits'] = form_text('post_credits', $kv['post_credits']);
	$input['isfirst'] = form_radio_yes_no('isfirst', $kv['isfirst']);
	$input['creditsfrom'] = form_text('creditsfrom', $kv['creditsfrom']);
	$input['creditsto'] = form_text('creditsto', $kv['creditsto']);

	include _include(APP_PATH.'plugin/sg_group/setting.htm');
} else {
	$kv = array();
	$kv['up_group'] = param('up_group', 0);
	$kv['create_credits'] = param('create_credits', 0);
	$kv['post_credits'] = param('post_credits', 0);
	$kv['isfirst'] = param('isfirst', 0);
	$kv['creditsfrom'] = param('creditsfrom', 0);
	$kv['creditsto'] = param('creditsto', 0);

	setting_set('sg_group', $kv);
	message(0, lang('save_successfully'));
}
?>