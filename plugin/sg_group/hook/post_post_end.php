 <?php exit;
		$return_html = param('return_html', 0);
		$sg_group = setting_get('sg_group');
		$uid AND user__update($uid, array('credits+'=>$sg_group['post_credits']));
		user_update_group($uid);
		if($return_html) {
			$filelist = array();
			$sg_group_pid = $pid;
			include _include(APP_PATH.'view/htm/post_list.inc.htm');
			$s = ob_get_clean();
			message(0, $s);
		} else {
			
			message(0, lang('create_post_sucessfully').lang('sg_creditsplus',  array('credits'=>$sg_group['post_credits'])));
		}
?>