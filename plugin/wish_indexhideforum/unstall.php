<?php

/*
	Xiuno BBS 4.0 URL别名
	admin/plugin-unstall-wish_aliasname.htm
*/

!defined('DEBUG') AND exit('Forbidden');

//删除数据库配置
if(setting_get('wish_indexhideforum')){
    setting_delete('wish_indexhideforum');
}

?>