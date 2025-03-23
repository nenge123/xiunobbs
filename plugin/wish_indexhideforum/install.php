<?php

/*
	Xiuno BBS 4.0 URL别名
	admin/plugin-unstall-wish_aliasname.htm
*/

!defined('DEBUG') AND exit('Forbidden');

//初始化插件配置
$wish_indexhideforum = setting_get('wish_indexhideforum');
if(empty($wish_indexhideforum)){
    $data['hide_forums'] = '';
    $data['also_hide_tops'] = 'yes';
    $data['show_in_nav'] = 'yes';
    setting_set('wish_indexhideforum', $data);
}

?>