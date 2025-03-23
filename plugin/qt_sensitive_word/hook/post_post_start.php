<?php exit;
		qt_check_sensitive_word($_REQUEST['message'], 'post_sensitive_words', $qt_error) AND message('message', lang('post_contain_sensitive_word') . $qt_error);
