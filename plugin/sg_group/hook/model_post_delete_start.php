 <?php exit;
	if(!$post['isfirst']) {
		$sg_group = setting_get('sg_group');
		$uid AND user__update($uid, array('credits-'=>$sg_group['post_credits']));
	}
?>