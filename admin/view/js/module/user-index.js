/**
 * 用户搜索
 */
export default class thread {
	constructor(elm,X) {
		this.X = X;
		const T = this;
		const btn = $(elm).find('[type="submit"]');
		const url = elm.action;
		elm.on('submit',async function(event){
			event.preventDefault();
			if(btn.prop('disabled')){
				return $.alert(btn.attr('loading-text') || lang('submit_loading'));;
			}
			btn.disabled();
			const postdata = await X.callMethod('FormData',this);
			const result = await T.getList(url,postdata);
			btn.disabled(false);
			result && T.setList(result);
		});
		$('#page-list').on('click','.page-link',async function(event){
			event.preventDefault();
			if(btn.prop('disabled')){
				return $.alert(btn.attr('loading-text') || lang('submit_loading'));;
			}
			btn.disabled();
			const postdata = await X.callMethod('FormData',elm);
			postdata.set('page',parseInt($(this).text())||0);
			const result = await T.getList(url,postdata);
			btn.disabled(false);
			result && T.setList(result);
		});
		$('button[data-type]').on('click', async function (event) {
			const acttype = this.getAttribute('data-type');
			const lists = document.querySelectorAll('[name^=delete]');
			if (acttype == 'checkall') {
				const bool = !lists[0].checked;
				lists.forEach(e => e.checked = bool);
				return;
			}
			const uids = Array.from(lists).filter(e => e.checked).map(e => e.value);
			if (uids.length) {
				if (acttype == 'clear') {
					$.confirm('真要删除吗?',()=>T.clear(this.getAttribute('data-url'),uids));
				}else{
					T.block(this.getAttribute('data-url'),uids,acttype);
				}
			} else {
				$.alert('请先勾选');
			}
		});
	}
	/**
	 * 批量删除用户
	 * @param {*} url 
	 * @param {*} uids 
	 * @returns 
	 */
	async clear(url,uids){
		const newurl = url + (url.indexOf('?') == -1 ? '?' : '&') + 'uids=' + uids.join('|');
		const E = $('#data-result');
		E.html('');
		E.css('white-space', 'pre');
		E.css('color', 'red');
		E.css('background-color', '#ffffffa3');
		return X.callMethod('createEventSource', newurl, {
			open(event) {
				console.log(event);
				if (event.data) {
					let p = JSON.parse(event.data);
					p && p.message && E.append(p.message + "\n");
				}
			},
			progress(event) {
				console.log(event);
				if (event.data) {
					let p = JSON.parse(event.data);
					if (p) {
						if (p.message) {
							E.append(p.message + "\n");
						}
						if (p.uid) {
							document.querySelector('#uid-' + p.uid).remove();
						}
					}
				}
			},
			close(event) {
				if (event.data) {
					let p = JSON.parse(event.data);
					if (p) {
						p && p.message && E.append(p.message + "\n");
						if (p.url) {
							$.reload(p.url,3000);
						}
					}

				}
			}
		});
	}
	/**
	 * 封禁/解封用户
	 * @param {*} url 
	 * @param {*} uids 
	 * @param {*} acttype 
	 */
	async block(url,uids,acttype){
		const data = await X.callMethod('FormData');
		uids.forEach(e => data.append('uids[]', e));
		data.set('action-type', acttype);
		$.ajax({
			type: 'POST',
			url,
			data,
			dataType: 'json',
			processData: false,
			contentType: false,
			/**
			 * @param {XMLHttpRequest} xhr 
			 */
			beforeSend(xhr) {
				xhr.setRequestHeader('ajax-fetch', 1);
			},
			success(r) {
				if (X.isOBJ(r)) {
					if (r.code === 0) {
						if(r.uids){
							r.uids.forEach(e => $('#uid-' + e).find('.thread-icon-delete').prop('hidden', acttype == 'block' ? false : true));
						}
					}
					if (r.message) $.alert(r.message);
				}
			}
		});
	}
	/**
	 * 获取用户列表
	 * @param {*} url 
	 * @param {*} data 
	 * @returns 
	 */
	getList(url, data) {
		const X = this.X;
		return new Promise(function (fn_success, fn_error) {
			$.ajax({
				type: 'POST',
				url,
				data,
				dataType: 'json',
				processData: false,
				contentType: false,
				/**
				 * @param {XMLHttpRequest} xhr 
				 */
				beforeSend(xhr) {
					xhr.setRequestHeader('ajax-fetch', 1);
				},
				success(r) {
					fn_success(r);
				},
				error(xhr, status, error) {
					const response = xhr.response;
					if (response && response.message) {
						$.alert(response.message);
					} else {
						X.callMethod('response_error', status, error);
					}
					fn_error(error);
				}
			});
		});
	}
	/**
	 * 生成用户列表
	 * @param {*} result 
	 * @returns 
	 */
	setList(result) {
		const X = this.X;
		if(!X.isOBJ(result) || !result.list.length){
			$('#user-total').text(0);
			$('#user-search-details').prop('open', true);
			$('#data-details').prop('open', false);
			return $.alert('结果为空!');
		}
		$('#user-search-details').prop('open', false);
		$('#data-details').prop('open', true);
		$('#user-total').text(result.total);
		if (result && result.list) {
			let html = '';
			for (let item of result.list) {
				let tpl_item = $('#tpl_datalist').html();
				Object.entries(item).forEach(entry => {
					tpl_item = tpl_item.replace(new RegExp('{' + entry[0] + '}', 'g'), entry[1]);
				});
				html += tpl_item;
			}
			$('#data-list').html(html);
		}
		if (result && result.pagelist) {
			let html = '';
			for (let item of result.pagelist) {
				let tpl_item = $('#tpl_page').html();
				tpl_item = tpl_item.replace(/{page}/ig, item);
				tpl_item = tpl_item.replace(/{active}/ig, item == result.page ? 'active' : '');
				html += tpl_item;
			}
			$('#page-list').html(html);
		}
	}
}