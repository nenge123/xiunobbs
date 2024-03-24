<?php
$mode = router_value(1,'login');
$myapp->data['memberhash'] = bin2hex($myapp->encrypt($attach['aid'],$myapp->ivcrypt($myapp->data['time'])));
include $myapp->template('member/'.$mode);