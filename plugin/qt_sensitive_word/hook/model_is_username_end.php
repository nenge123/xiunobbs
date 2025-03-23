<?php exit;

 elseif(qt_check_sensitive_word($username, 'username_sensitive_words', $er)) {
		$err = lang('username_contain_sensitive_word') . $er;
		return FALSE;
	}