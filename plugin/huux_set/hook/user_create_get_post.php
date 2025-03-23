<?php exit;

!empty($conf['user_create_io']) && message(0, jump('对不起, 已经关闭会员注册', http_referer(), 1));

?>