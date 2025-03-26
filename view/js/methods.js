export default {
	response_error(type){
		switch(true){
			case type>=500:
				$.alert(lang['http_error_500']);
			break;
			case type==404:
				$.alert(lang['http_error_404']);
			break;
			case type==403:
				$.alert(lang['http_error_403']);
			break;
			case type==0:
			case type>400:
				$.alert(lang['http_error_lose']);
			break;
		}
	},
}