<?php
/*
	Xiuno BBS 4.0 用户组升级增强版
	插件由xiuno非官方提供：http://www.xiuno.top/
*/

!defined('DEBUG') AND exit('Forbidden');
$r = kv_delete('sg_group');
$r = setting_delete('sg_group');
$r === FALSE AND message(-1, '卸载失败');

?>