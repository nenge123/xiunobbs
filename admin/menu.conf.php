<?php
return array(
	'index'=>array(
		'text'=>MyApp::Lang('admin_index'), 
		'icon'=>'mdi-home-analytics', 
		'tab'=> array (
			'index'=>array('url'=>MyApp::url('index'), 'text'=>MyApp::Lang('site_stat_info')),
			'phpinfo'=>array('url'=>MyApp::url('index/phpinfo'), 'text'=>MyApp::Lang('server_info')),
		)
	),
	'setting' => array(
		'text'=>MyApp::Lang('setting'), 
		'icon'=>'icon-cog', 
		'tab'=> array (
			'index'=>array('url'=>MyApp::url('setting'), 'text'=>MyApp::Lang('admin_setting_base')),
			'smtp'=>array('url'=>MyApp::url('setting/smtp'), 'text'=>MyApp::Lang('admin_setting_smtp')),
			'time'=>array('url'=>MyApp::url('setting/time'), 'text'=>MyApp::Lang('admin_setting_time')),
			'rewrite'=>array('url'=>MyApp::url('setting/rewrite'), 'text'=>MyApp::Lang('admin_setting_rewrite')),
			'clear'=>array('url'=>MyApp::url('setting/clear'), 'text'=>MyApp::Lang('admin_other_cache')),
		)
	),
	'forum' => array(
		'text'=>MyApp::Lang('forum_admin'), 
		'icon'=>'icon-comment',
		'tab'=> array (
			'index'=>array('url'=>MyApp::url('forum'), 'text'=>MyApp::Lang('forum_list')),
			'thread'=>array('url'=>MyApp::url('forum/thread'), 'text'=>MyApp::Lang('admin_thread_batch')),
		)
	),
	'user' => array(
		'text'=>MyApp::Lang('user'), 
		'icon'=>'icon-user',
		'tab'=> array (
			'index'=>array('url'=>MyApp::url('user'), 'text'=>MyApp::Lang('admin_user_list')),
			'group'=>array('url'=>MyApp::url('user/group'), 'text'=>MyApp::Lang('admin_user_group')),
			'create'=>array('url'=>MyApp::url('user/create'), 'text'=>MyApp::Lang('admin_user_create')),
		)
	),
	'plugin' => array(
		'url'=>MyApp::url('plugin'), 
		'text'=>MyApp::Lang('plugin'), 
		'icon'=>'icon-cogs',
		'tab'=> array (
			'index'=>array('url'=>MyApp::url('plugin'), 'text'=>MyApp::Lang('admin_plugin_enable')),
			'disable'=>array('url'=>MyApp::url('plugin/disable'), 'text'=>MyApp::Lang('admin_plugin_disable')),
			'install'=>array('url'=>MyApp::url('plugin/install'), 'text'=>MyApp::Lang('admin_plugin_install')),
			'unstall'=>array('url'=>MyApp::url('plugin/unstall'), 'text'=>MyApp::Lang('admin_plugin_unstall')),
			'all'=>array('url'=>MyApp::url('plugin/all'), 'text'=>MyApp::Lang('admin_plugin_all')),
		)
	)
);