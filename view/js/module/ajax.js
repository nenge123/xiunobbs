/**
 * @var  X xiuno
 */
export default {
	onajax(elm, type, domId) {
		const X = this;
		const value = elm.getAttribute(type);
		elm.removeAttribute(type);
		if (value && X.ajaxs.has(value)) {
			return X.callAjax(value, elm, domId);
		}
		if(type=='onpost'){
			if(X.isFrom(elm)||X.isFrom(elm.from)){
				$(elm.from||elm).submit(async function (event) {
					event.preventDefault();
					await X.callAjax('onpost_before',this)
					return X.callAjax('formpost',this);
				});
			}else{
				$(elm).attr('ajax-type',value);
				$(elm).on('click',function(event){
					event.preventDefault();
					X.callAjax('linkpost',this);
				})
			}
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
		$(elm).submit(async function (event) {
			event.preventDefault();
			const jform = $(this);
			//发送前提交
			const postdata = await X.callMethod('FormData',this);
			if (!await X.callAjax('userlogin_await',this,postdata)) {
				if (!postdata.has('password')) {
					$.alert(lang['password_empty']);
					return jform.disabled(false);
				}
				await import(X.jsroot + 'spark-md5.min.js');
				postdata.set('password',SparkMD5.hash(postdata.get('password')));
				X.callAjax('formpost',jform,postdata);
			}
		});
	},
	async formpost(jform,data){
		const X = this;
		await X.callAjax('formpost_before',jform,data)
		let processData = true;
		jform = X.callMethod('get_form_elm',jform);
		const submitButton = $(jform).find('[type=submit]');
		if(submitButton.prop('disabled')){
			return $.alert(submitButton.attr('loading-text') || lang('submit_loading'));;
		}
		submitButton.disabled();
		if(!data)data = await X.callMethod('FormData',jform);
		if(X.isPOST(data))processData = false;
		$(jform).find('[name]').removeClass('is-invalid');
		const url = $(jform).attr('action') || location.href;
		$.ajax({
			elm:$(jform)[0],
			type: 'POST',
			url,
			data,
			dataType: 'json',
			processData,
			complete(xhr,status){
				submitButton.disabled(!1);
			}
		});
	},
	async linkpost(elm){
		const E = $(elm);
		const url = E.attr('href')||E.attr('data-url')||location.href;
		const data = E.data()||{};
		if(data.url){
			delete data.url;
		}
		E.fadeOut();
		let xx = new FormData();
		xx.set('yy',1);
		$.ajax({type: 'POST',url,data,elm,dataType: 'binary',complete(){E.delay(1000).fadeIn();}});
	},
	ajax_result(response,elm,type,xhr){
		if(response instanceof Document){
			return this.callAjax('ajax_result_dom',response,elm,type,xhr);
		}else if(this.isOBJ(response)){
			return this.callAjax('ajax_result_json',response,elm,type,xhr);
		}else if(typeof response =='string'){
			return this.callAjax('ajax_result_text',response,elm,type,xhr);
		}
		$.alert(lang['http_error_response']);
	},
	ajax_result_dom(response,elm,type,xhr){
		console.log(response,elm,type,xhr);
	},
	ajax_result_json(r,elm,type,xhr){
		if (r.message) $.alert(r.message);
		if(!isNaN(r.code))r.code = parseInt(r.code);
		if (typeof r.code == 'string') {
			const ipt =  $(elm).find('[name=' + r.code + ']');
			ipt.addClass('is-invalid');
			ipt.val('');
		} else if (r.code == 0) {
			if(r.delay||r.url){
				const deply = r.delay || 2;
				return $.alert(r.message).delay(deply*1000).location(r.url && r.url || './');
			}
			if(r.event){
				elm.toEvent(r.event,r);
			}
			if(r.method){
				this.callMethod(r.method,elm,r);
			}
		}
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
                processData: false,
				setHeaders(xhr){
					xhr.setRequestHeader('ajax-image',1);
				},success,error
			});
		},'image/*');
	}
}