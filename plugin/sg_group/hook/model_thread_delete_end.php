 <?php exit;
$sg_group = setting_get('sg_group');
user__update($uid, array('credits-'=>$sg_group['create_credits']));
?>