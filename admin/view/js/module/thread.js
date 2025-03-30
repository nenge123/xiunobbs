/**
 * 主题批量删除处理脚本
 */
export default class thread {
	constructor(elm,X) {
		const T = this;
		this.X = X;
		const btn = $(elm).find('[type="submit"]');
		const url = elm.action;
		elm.on('submit', async function (event) {
			event.preventDefault();
			btn.disabled();
			const data = await X.callMethod('FormData', this);
			const result = await T.getList(url, data).catch(e => false);
			btn.disabled(false);
			result && T.setList(result);
		});
		$(elm).on('click', '.page-link', async function (event) {
			event.preventDefault();
			btn.disabled();
			const data = await X.callMethod('FormData', elm);
			data.set('page', this.getAttribute('id').match(/\d+/)[0]);
			const result = await T.getList(url, data).catch(e => false);
			btn.disabled(false);
			result && T.setList(result);
		});
		$('button[data-type]').on('click', async function (event) {
			const acttype = this.getAttribute('data-type');
			const lists = document.querySelectorAll('[id^=input-]');
			if (acttype == 'check') {
				const bool = !lists[0].checked;
				lists.forEach(e => e.checked = bool);
				return;
			}
			const tids = Array.from(lists).filter(e => e.checked).map(e => e.value);
			if (tids.length) {
				if (acttype == 'delete') {
					const newurl = url + (url.indexOf('?') == -1 ? '?' : '&') + 'tids=' + tids.join('|');
					const E = $('#delete-result');
					E.html('');
					E.css('white-space', 'pre');
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
									if (p.tid) {
										document.querySelector('#tid-' + p.tid).remove();
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
										$(E).delay(3000).location(p.url);
									}
								}

							}
						}
					});
				}
				const data = await X.callMethod('FormData');
				tids.forEach(e => data.append('tids[]', e));
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
								switch (acttype) {
									case 'closed':
									case 'open':
										tids.forEach(e => $('#tid-' + e).find('.thread-icon-lock').prop('hidden', acttype == 'closed' ? false : true));
										break;
									case 'block':
									case 'unblock':
										tids.forEach(e => $('#tid-' + e).find('.thread-icon-delete').prop('hidden', acttype == 'block' ? false : true));
										break;
								}
							}
							if (r.message) $.alert(r.message);
						}
					}
				});
			} else {
				$.alert('请先勾选');
			}
		});
	}
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
	setList(result) {
		const X = this.X;
		if(!X.isOBJ(result) || !result.list.length){
			$('#threads-length').text(0);
			$('#thread-search-details').prop('open', true);
			$('#data-details').prop('open', false);
			return $.alert('结果为空!');
		}
		$('#thread-search-details').prop('open', false);
		$('#data-details').prop('open', true);
		$('#threads-length').text(result.maxlen);
		if (result && result.list) {
			let html = '';
			for (let item of result.list) {
				let tpl_item = $('#thread-list-item').text();
				Object.entries(item).forEach(entry => {
					tpl_item = tpl_item.replace(new RegExp('{' + entry[0] + '}', 'g'), entry[1]);
				});
				tpl_item = tpl_item.replace(/{url}/ig, X.webroot + '?thread-' + item.tid);
				tpl_item = tpl_item.replace(/{closed_hidden}/ig, item['closed'] === 1 ? '' : 'hidden');
				tpl_item = tpl_item.replace(/{deleted_hidden}/ig, item['deleted'] === 1 ? '' : 'hidden');
				html += tpl_item;
			}
			$('#data-list').html(html);
		}
		if (result && result.pagelist) {
			let html = '';
			for (let item of result.pagelist) {
				let tpl_item = $('#thread-list-page').text();
				tpl_item = tpl_item.replace(/{page}/ig, item);
				tpl_item = tpl_item.replace(/{active}/ig, item == result.page ? 'active' : '');
				html += tpl_item;
			}
			$('#page-list').html(html);
		}
	}
}