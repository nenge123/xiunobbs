<?php exit;
		qt_check_sensitive_word($thread['subject'], 'post_sensitive_words', $qt_error) AND message('subject', lang('thread_contain_sensitive_word') . $qt_error);
		qt_check_sensitive_word($thread['message'], 'post_sensitive_words', $qt_error) AND message('message', lang('post_contain_sensitive_word') . $qt_error);