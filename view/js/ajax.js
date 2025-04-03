/**
 * @var  X xiuno
 */
export default {
	onajax(elm, type, domId) {
		const X = this;
		const value = elm.getAttribute(type);
		elm.removeAttribute(type);
		if (value && value != 'true') {
			return X.callAjax(value, elm, domId);
		}
		if(type=='onpost'){
			elm = X.callMethod('get_form_elm',elm);
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
		elm = X.callMethod('get_form_elm',elm);
		$(elm).on('submit', async function (event) {
			event.preventDefault();
			await import(X.jsroot + 'spark-md5.min.js');
			const jform = $(this);
			//发送前提交
			const postdata = await X.callMethod('FormData',this);
			if (!await X.callAjax('userlogin_await',this,postdata)) {
				if (!postdata.has('password')) {
					$.alert(lang['password_empty']);
					return jform.disabled(false);
				}
				postdata.set('password',SparkMD5.hash(postdata.get('password')));
				X.callAjax('formpost',jform,postdata);
			}
		});
	},
	async formpost(jform,data){
		const X = this;
		let processData = false;
		jform = X.callMethod('get_form_elm',jform);
		const submitButton = $(jform).find('[type=submit]');
		if(submitButton.prop('disabled')){
			return $.alert(submitButton.attr('loading-text') || lang('submit_loading'));;
		}
		submitButton.disabled();
		if(!X.isPOST(data)){
			if(!data){
				data = await X.callMethod('FormData',jform);
			}else if(X.isOBJ(data)){
				processData = true;
			}else{
				return $.alert('输入数据不合法!')
			}
		}
		$(jform).find('[name]').removeClass('is-invalid');
		$.ajax({
			type: 'POST',
			url:jform.getAttribute('action'),
			data,
			dataType: 'json',
			processData,
			contentType:processData,
			timeout: 6000000,
			/**
			 * @param {XMLHttpRequest} xhr 
			 */
			beforeSend(xhr){
				xhr.setRequestHeader('ajax-fetch',1);
			},
			success(r) {
				submitButton.disabled(!1);
				if (X.isOBJ(r)) {
					if (r.message) $.alert(r.message);
					if(!isNaN(r.code))r.code = parseInt(r.code);
					if (typeof r.code == 'string') {
						const ipt =  $(jform).find('[name=' + r.code + ']');
						ipt.addClass('is-invalid');
						ipt.val('');
					} else if (r.code == 0) {
						if(r.delay||r.url){
							const deply = r.delay || 2;
							return $.alert(r.message).delay(deply*1000).location(r.url && r.url || './');
						}
					}
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

	},
	/**
	 * 上传一张图片
	 */
	uploadimage(url,success,post,error){
		const X = this;
		X.callMethod('upload',async files=>{
			const data = await X.callMethod('FormData');
			const file = await X.callMethod('formatImage',files[0]);
			if(!file) return ;
			data.append('file',file);
			if(post){
				for(const i in post){
					data.append(i,post[i]);
				}
			}
			$.ajax({
				type: 'POST',
				url,
				data,
				dataType: 'json',
				timeout: 6000000,
                processData: false,
                contentType: false,
				beforeSend(xhr){
					xhr.setRequestHeader('ajax-fetch',1);
					xhr.setRequestHeader('ajax-image',1);
				},
				success,
				error
			});
		},'image/*');
	}
}