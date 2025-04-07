<?php

namespace model;

use MyApp;

class user
{
	public static function avatar(array $user = array()): string
	{
		if(!empty($user)&&!empty($user['avatar'])):
			$dir = substr(sprintf("%09d", $user['uid']), 0, 3);
			// hook model_user_format_avatar_url_before.php
			return MyApp::upload_site('avatar/' . $dir . '/' . $user['uid'] . '.png?' . $user['avatar']);
		endif;
	return MyApp::view_site('img/avatar.png');
	}
}
