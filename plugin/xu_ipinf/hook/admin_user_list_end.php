<?php exit;
function zharrip($ipip)
{
	$ipip[0] != '中国' && $ip = $ipip[0];

	if ($ipip[1] != '') {
		$ip = !isset($ip) ? '' : $ip . '-';
		$ip = $ip . $ipip[1];
		$ipip[2] != '' && $ipip[2] != $ipip[1] &&  $ip = $ip . '-' . $ipip[2];
	}
	return $ip;
}
include APP_PATH . '/plugin/xu_ipinf/IP4datx.class.php';
$ipfind = new IP;
foreach ($userlist as &$_user) {
	$ipip = $ipfind->find($_user['create_ip_fmt']);
	$_user['create_ip_fmt'] = $_user['create_ip_fmt'] . '(' . zharrip($ipip) . ')';
}
//message(0,);