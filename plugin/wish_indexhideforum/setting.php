<?php
/*
	唯诚网络出品91wc.net
	技术维护QQ：1198845956
*/
!defined('DEBUG') AND exit('Access Denied.');
if($method == 'GET') {
    //展示配置数据
	$setting['wish_indexhideforum'] = setting_get('wish_indexhideforum');
	include _include(APP_PATH.'plugin/wish_indexhideforum/setting.htm');
} else {
    //保存配置
    $data['hide_forums'] = param('wish_indexhideforum_hide_forums', '', FALSE);
    $data['also_hide_tops'] = param('wish_indexhideforum_also_hide_tops', 'no', FALSE);
    $data['show_in_nav'] = param('wish_indexhideforum_show_in_nav', 'no', FALSE);
	setting_set('wish_indexhideforum', $data);

	message(0, '修改成功');
}
