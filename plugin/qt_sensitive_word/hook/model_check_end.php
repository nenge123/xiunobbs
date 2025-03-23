<?php exit;
function qt_check_sensitive_word($s, $type, &$error) {
		$sensitive_words = kv_get('qt_sensitive_words');
		if(!isset($sensitive_words[$type])  || !is_array($sensitive_words[$type])) {
			return false;
		}
		foreach($sensitive_words[$type] as $v) {
			if(strpos(strtolower($s),strtolower($v)) !== false) {
				$error = $v;
				return true;
			}
		}
		return false;
	}

