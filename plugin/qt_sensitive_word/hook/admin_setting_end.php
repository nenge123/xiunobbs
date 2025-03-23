<?php exit;

elseif($action == 'sensitive_word') {
	
	// hook admin_setting_sensitive_word_get_post.php
	
	if($method == 'GET') {
		
		// hook admin_setting_sensitive_word_get_start.php
		
		$sensitive_words = kv_get('qt_sensitive_words');
		if(!$sensitive_words) {
			$sensitive_words = array('username_sensitive_words'=>array(), 'post_sensitive_words'=>array());
		}
		$sensitive_words['username_sensitive_words'] = implode(' ', $sensitive_words['username_sensitive_words']);
		$sensitive_words['username_sensitive_words'] = htmlspecialchars($sensitive_words['username_sensitive_words']);
		$sensitive_words['post_sensitive_words'] = implode(' ', $sensitive_words['post_sensitive_words']);
		$sensitive_words['post_sensitive_words'] = htmlspecialchars($sensitive_words['post_sensitive_words']);
		$input = array();
		$input['username_sensitive_words'] = form_textarea('username_sensitive_words', $sensitive_words['username_sensitive_words'], '100%', 200);
		$input['post_sensitive_words'] = form_textarea('post_sensitive_words', $sensitive_words['post_sensitive_words'], '100%', 300);
		
		$header['title'] = lang('sensitive_word_setting');
		$header['mobile_title'] =lang('sensitive_word_setting');
		
		// hook admin_setting_sensitive_word_get_end.php
		
		include _include(APP_PATH.'plugin/qt_sensitive_word/view/htm/setting_sensitive_word.htm');
		
	} else {
		
		$username_sensitive_words = param('username_sensitive_words', '', FALSE);
		$post_sensitive_words = param('post_sensitive_words', '', FALSE);
		// hook admin_setting_sensitive_word_post_start.php
		
		$username_sensitive_words = str_replace("　 ", ' ', $username_sensitive_words);
		$username_sensitive_words = preg_replace('#\s+#is', ' ', $username_sensitive_words);
		$username_sensitive_words = explode(' ', $username_sensitive_words);
		$post_sensitive_words = str_replace("　 ", ' ', $post_sensitive_words);
		$post_sensitive_words = preg_replace('#\s+#is', ' ', $post_sensitive_words);
		$post_sensitive_words = explode(' ', $post_sensitive_words);
		kv_set('qt_sensitive_words', array('username_sensitive_words'=>$username_sensitive_words, 'post_sensitive_words'=>$post_sensitive_words));
		// hook admin_setting_sensitive_word_post_end.php
		
		message(0, lang('modify_successfully'));
	}

}