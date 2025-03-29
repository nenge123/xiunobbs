X.ajaxs.set(
	'adimin_thread_lock',
	/**
	 * 封禁 锁帖 开关
	 * @param {HTMLElement} elm 
	 */
	function (elm) {
		elm.on('click', async function (event) {
			const E = this;
			if (E.disabled) return;
			E.disabled = true;
			const url = E.getAttribute('data-url');
			const type = E.getAttribute('data-type');
			let tids = Array.from(document.querySelectorAll(E.getAttribute('data-match'))).filter(e => e.checked).map(e => e.value);
			if (tids.length) {
				const data = await X.callMethod('FormData');
				tids.forEach(e => data.append('tids[]', e));
				$.ajax({
					type: 'POST',
					url,
					data,
					dataType: 'json',
					processData: false,
					contentType: false,
					timeout: 6000000,
					/**
					 * @param {XMLHttpRequest} xhr 
					 */
					beforeSend(xhr) {
						xhr.setRequestHeader('ajax-fetch', 1);
					},
					success(r) {
						E.disabled = false;
						if (X.isOBJ(r)) {
							if (r.code === 0) {
								tids.forEach(e => {
									if (type == 'closed' || type == 'open') {
										let m = document.querySelector('#closed-' + e);
										m && (m.hidden = (type == 'closed' ? false : true));
									} else if (type == 'block' || type == 'unblock') {
										let m = document.querySelector('#block-' + e);
										m && (m.hidden = (type == 'block' ? false : true));
									}
								});
							}
							if (r.message) $.alert(r.message);
						} else {
							$.alert(lang['http_error_response']);
						}
					},
					error(xhr, status, error) {
						E.disabled = false;
						const response = xhr.response;
						if (response && response.message) {
							$.alert(response.message);
						} else {
							X.callMethod('response_error', status, error);
						}
					}
				});
			} else {
				$.alert('请先勾选');
			}
			this.disabled = false;
		})
});

X.methods.set(
	'adimin_thread_delete',
	function (elm) {
	elm.on('click', function () {
		if (this.disabled) return;
		this.disabled = true;
		let tids = Array.from(document.querySelectorAll(this.getAttribute('data-match'))).filter(e => e.checked).map(e => e.value).join(',');
		let url = this.getAttribute('data-url') || this.form.getAttribute('action');
		url += (url.indexOf('?') == -1 ? '?' : '&') + 'tids=' + tids;
		const E = document.querySelector('#delete-result');
		E.style.cssText = 'white-space: pre;';
		if (tids) {
			X.callMethod('createEventSource', url, {
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
		} else {
			$.alert('请先勾选');
		}
		this.disabled = false;
	})
});