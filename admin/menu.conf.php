<?php

return array(
	'setting' => array(
		'url'=>MyApp::url('setting/base'), 
		'text'=>lang('setting'), 
		'icon'=>'icon-cog', 
		'tab'=> array (
			'base'=>array('url'=>MyApp::url('setting/base'), 'text'=>lang('admin_setting_base')),
			'smtp'=>array('url'=>MyApp::url('setting/smtp'), 'text'=>lang('admin_setting_smtp')),
		)
	),
	'forum' => array(
		'url'=>MyApp::url('forum/list'), 
		'text'=>lang('forum'), 
		'icon'=>'icon-comment',
		'tab'=> array (
		)
	),
	'thread' => array(
		'url'=>MyApp::url('thread/list'), 
		'text'=>lang('thread'), 
		'icon'=>'icon-comment',
		'tab'=> array (
			'list'=>array('url'=>MyApp::url('thread/list'), 'text'=>lang('admin_thread_batch')),
		)
	),
	'user' => array(
		'url'=>MyApp::url('user/list'), 
		'text'=>lang('user'), 
		'icon'=>'icon-user',
		'tab'=> array (
			'list'=>array('url'=>MyApp::url('user/list'), 'text'=>lang('admin_user_list')),
			'group'=>array('url'=>MyApp::url('group/list'), 'text'=>lang('admin_user_group')),
			'create'=>array('url'=>MyApp::url('user/create'), 'text'=>lang('admin_user_create')),
		)
	),
	'other' => array(
		'url'=>url('other'), 
		'text'=>lang('other'), 
		'icon'=>'icon-wrench',
		'tab'=> array (
			'cache'=>array('url'=>MyApp::url('other/cache'), 'text'=>lang('admin_other_cache')),
		)
	),
	'plugin' => array(
		'url'=>MyApp::url('plugin'), 
		'text'=>lang('plugin'), 
		'icon'=>'icon-cogs',
		'tab'=> array (
			'local'=>array('url'=>MyApp::url('plugin'), 'text'=>lang('admin_plugin_local_list')),
		)
	)
);

?>