 <?php exit;
$credits = $sg_group['create_credits'];
$message = lang('sg_creditsplus',  array('credits'=>$sg_group['create_credits']));
if($sg_group['isfirst'] == 1) {
	$t = $user_create_date['create_date'] - runtime_get('cron_2_last_date');
	if($t < 0) {
		$creditsrand = rand($sg_group['creditsfrom'], $sg_group['creditsto']);
		$credits += $creditsrand;
		$message = lang('sg_isfirst_creditsplus', array('credits'=>$sg_group['create_credits'], 'creditsplus'=>$creditsrand));
	}
}
$_SESSION['sg_group_message'] = $message;
$uid AND user__update($uid, array('credits+'=>$credits));
$uid AND user_update_group($uid);
?>