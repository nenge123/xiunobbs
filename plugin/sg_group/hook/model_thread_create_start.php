 <?php exit;
$sg_group = setting_get('sg_group');
$user_mythread = db_find_one('mythread',  array('uid'=>$uid), array('tid'=>-1), array('tid'));
$user_create_date = db_find_one('thread', array('tid'=>$user_mythread['tid']), array(), array('create_date'));
?>