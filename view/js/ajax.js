export default {
	onajax(elm, type, domId) {
		const X = this;
		const value = elm.getAttribute(type);
		elm.removeAttribute(type);
		if (value && value != 'true') {
			return X.callAjax(value, elm, domId);
		}
		if(type=='onpost'){
			if (!X.isFrom(elm)) elm = elm.from;
			$(elm).on('submit', async function (event) {
				event.preventDefault();
				return X.callAjax('formpost',this);
			});
		}
	},
	/**
	 * 登录处理函数
	 * @param {HTMLFormElement} elm 
	 * @param {*} domId 
	 */
	userlogin(elm, domId) {
		const X = this;
		if (!X.isFrom(elm)) elm = elm.from;
		$(elm).on('submit', async function (event) {
			event.preventDefault();
			await import(X.jsroot + 'spark-md5.min.js');
			const jform = $(this);
			//发送前提交
			const postdata = jform.serializeObject();
			if (!await X.callAjax('userlogin_await',this,postdata)) {
				if (!postdata.password) {
					$.alert(lang['password_empty']);
					return jform.disabled(false);
				}
				postdata.password = SparkMD5.hash(postdata.password);
				X.callAjax('formpost',jform,postdata);
			}
		});
	},
	formpost(jform,data){
		if (!X.isFrom(jform)){
			jform = $(jform.from);
		}else if(!(jform instanceof jQuery)){
			jform = $(jform);
		}
		const submitButton = jform.find('[type=submit]');
		if(submitButton.prop('disabled')){
			return $.alert(submitButton.attr('loading-text') || lang('submit_loading'));;
		}
		submitButton.disabled();
		if(!data){
			data = jform.serializeObject();
		}
		$.ajax({
			type: 'POST',
			url:jform.attr('action'),
			data,
			dataType: 'json',
			timeout: 6000000,
			/**
			 * 
			 * @param {XMLHttpRequest} xhr 
			 */
			beforeSend(xhr){
				xhr.setRequestHeader('ajax-fetch',1);
			},
			success(r) {
				submitButton.disabled(!1);
				if (X.isOBJ(r)) {
					if(!isNaN(r.code))r.code = parseInt(r.code);
					if (typeof r.code == 'string') {
						return jform.find('[name=' + r.code + ']').val('');
					} else if (r.code == 0) {
						if(r.delay||r.url){
							const deply = r.delay || 2;
							return $.alert(r.message).delay(deply*1000).location(r.url && r.url || './');
						}
					}
					if (r.message) $.alert(r.message);
				} else {
					$.alert(lang['http_error_response']);
				}
			},
			error(xhr, status, error) {
				submitButton.disabled(!1);
				const response = xhr.response;
				if (response && response.message) {
					$.alert(response.message);
				} else {
					X.callMethod('response_error', status, error);
				}
			}
		});

	}
}