 <?php exit;
$sg_group = setting_get('sg_group');
if($sg_group['up_group'] == 1) {
	$n = $user['credits']; 
} elseif($sg_group['up_group'] == 2) {
	$n = $user['credits'] + $user['threads'];
} elseif($sg_group['up_group'] == 3) {
	$n = $user['credits'] + $user['posts'];
}
?>