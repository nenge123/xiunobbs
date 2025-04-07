export class myeditorbase {
	css_href = '//unpkg.com/aieditor@1.2.6/dist/style.css';
	js_href = '//unpkg.com/aieditor@1.2.6/dist/index.js';
	wasm_js = '//unpkg.com/wasm-webp@0.0.2/dist/esm/webp-wasm.js';
	wasm_buf = '//unpkg.com/wasm-webp@0.0.2/dist/esm/webp-wasm.wasm';
	zip_js = '//unpkg.com/@zip.js/zip.js@2.7.53/dist/zip.min.js';
	form_js = '//unpkg.com/formdata-polyfill@4.0.10/formdata.min.js';
	isMobile = navigator.userAgent.match(/(iphone|android|QQBrowser|QBWeb)/i);
	constructor(elm) {
		this.toast = new mytoast();
		this.elm = elm;
		/**
		 * @type {HTMLFormElement} form
		 */
		this.form = elm.form;
		this.action = this.action || elm.form.action;
	}
	setup(editor) {
		const E = this;
		E.tinymce = editor;
		self.editor = this;
		//majorVersion
		for (let key in this.IconList) {
			editor.ui.registry.addIcon(key, '<svg height="24" width="24">' + this.IconList[key] + '</svg>');
		}
		editor.ui.registry.addMenuButton('myattach', {
			text: '附件',
			fetch: (callback) => {
				const items = [{
					type: 'menuitem',
					icon: 'insertImg',
					text: '图片/附件',
					onAction: () => {
						E.upload(async files => {
							if (files.length > 1) {
								E.confirm('逐个上传?否则压缩上传', async state => {
									if (state) {
										Array.from(files, file => E.file_upload(file));
									} else {
										let toast = E.toast.add('预压缩', files[0].name + '等多个文件.');
										const result = await E.toZip(files, str => toast.msg(str));
										$(toast).toast('hide');
										result && result[0] && E.file_upload(result[0]);
									}
								});
							} else {
								return E.file_upload(files[0]);
							}
						});
					}
				},
					'image',
				{
					type: 'menuitem',
					icon: 'attachlist',
					text: '附件列表',
					onAction: function (event) {
						E.showattach();
					}
				}

				];
				callback(items);
			}
		});
		editor.ui.registry.addMenuButton('hlist55', {
			text: '标题',
			fetch: callback => {
				callback(['h1', 'h2', 'h3', 'h4', 'h5', 'h6'])
			}
		});
	}
	async initEditor() {
		const E = this;
		if (!self.loadingEditor) {
			self.loadingEditor = new Promise(async back => {
				let toast = E.toast.add('tinymce编辑器加载', '...');
				const FormData = self.FormData;
				if (!FormData || !FormData.prototype || !FormData.prototype.set || !FormData.prototype.entries) {
					/** 防止QQ浏览器恶意篡改 */
					await E.importFile(E.form_js);
				}
				for (let file of E.inclueFile) {
					if (!file) continue;
					await E.importFile(E.tinymce_dir + file, toast);
				}
				if (E.csstext.length) {
					let $zindex = false;
					for (let styles of Array.from(document.styleSheets)) {
						if (!styles.href || styles.href.match(location.origin)) {
							if (styles.cssRules) {
								for (let value of E.csstext) {
									styles.insertRule(value, styles.cssRules.length);
								}
								$zindex = true;
								break;
							}
						}
					}
					if (!$zindex) {
						document.body.appendChild(document.createElement('style')).innerHTML = E.csstext.join('');
					}
				}
				$(toast).toast('hide');
				E.LANG && self.tinyMCE.addI18n(E.langName||'en', E.LANG||{});
				back(self.tinyMCE);
			});
		}
		return self.loadingEditor;
	}
	async importFile(url, toast) {
		return await import(await this.importLink(url, str => toast && toast.msg(str)));
	}
	async importLink(url, back) {
		return await this.fetchLink(url, len => back(Math.ceil(len / 1024) + 'KB'));
	}
	async fetchLink(url, progress) {
		const table = await this.table();
		let blob = await table.get(url);
		if (!blob) {
			blob = await this.fetchpProgress(url, progress);
			if (!blob) return url;
			await table.put(url, blob);
		}
		if (blob instanceof Blob) return URL.createObjectURL(blob);
		return url;
	}

	async loadwasm(progress) {
		const E = this;
		progress && progress('启用WebAssembly压缩');
		//由于部分浏览器不支持webp压缩 尝试wasm压缩 例如苹果IOS16--
		const wasmBinary = await this.fetchBuf(this.wasm_buf, size => progress && progress('wasm核心已下载:' + Math.ceil(size / 1024) + 'KB'));
		if (wasmBinary) {
			const {
				default: Module
			} = await import(await this.importLink(this.wasm_js, str => progress && progress('wasm脚本已下载:' + str)));
			const wasmwebp = await Module({
				wasmBinary,
				locateFile() {
					return E.wasm_buf;
				}
			});
			await wasmwebp.ready;
			wasmwebp.wasmBinary = null;
			delete wasmwebp.wasmBinary;
			progress && progress('WebAssembly启动');
			return wasmwebp;
		}
		return false;
	}
	async fetchBuf(url, progress) {
		const table = await this.table();
		let blob = await table.get(url);
		if (!blob) {
			blob = await (await this.fetchpProgress(url, progress)).arrayBuffer();
			if (!blob) return;
			blob = new Uint8Array(blob);
			await table.put(url, blob);
		}
		return blob;
	}
	async fetchpProgress(url, progress) {
		const response = await fetch(url).catch(e => false);
		if (!response || response.ok === false) return;
		const steam = [];
		let steamlength = 0;
		const reader = response.body.getReader();
		while (true) {
			const {
				done,
				value
			} = await reader.read();
			if (done) {
				break;
			}
			steamlength += value.byteLength;
			steam.push(value);
			progress && progress(steamlength);
		}
		const filename = url.split('/').pop();
		let type = filename.split('.').pop();
		switch (type) {
			case 'js':
				type = 'application/javascript';
				break;
			case 'css':
				type = 'text/css';
				break;
			case 'wasm':
				type = 'application/wasm';
				break;
		}
		return new File(steam, filename, { type });
	}
	table() {
		return new Promise(back => {
			const req = self.indexedDB.open('cdn-files');
			req.addEventListener('upgradeneeded', function (event) {
				const db = event.target.result;
				db.createObjectStore('files');
			});
			req.addEventListener('success', function (event) {
				const db = event.target.result;
				return back({
					db,
					async transaction() {
						const transaction = await this.db.transaction(['files'], "readwrite");
						return transaction.objectStore('files');
					},
					get(name) {
						return new Promise(async r => (await this.transaction()).get(name).onsuccess = e => r(e.target.result))
					},
					put(name, data) {
						return new Promise(async r => (await this.transaction()).put(data, name).onsuccess = e => r(e.target.result))
					}
				});
			});
		});
	}
	async ajax(ARG) {
		return new Promise(complete_callback => {
			const request = new XMLHttpRequest;
			request.addEventListener('readystatechange', function (event) {
				const {
					HEADERS_RECEIVED,
					DONE
				} = this;
				switch (this.readyState) {
					case DONE: {
						if (request.response) {
							complete_callback(request.response);
						} else {
							complete_callback({
								code: -1,
								message: '上传失败,<b style="color:red;">文件太大上传超时</b>或者<b style="color:red;">请求被拒绝!</b>'
							});
						}
						break;
					}
				}
			});
			request.addEventListener('error', function () {
				complete_callback({
					code: -1,
					message: '上传请求失败!'
				});
			})
			request.upload.addEventListener('progress', e => ARG.progress && ARG.progress(e.loaded, e.total));
			request.open(ARG.method, ARG.href);
			ARG.headers = ARG.headers || {};
			ARG.headers['ajax-fetch'] = true;
			Object.entries(ARG.headers || {}).forEach(entry => request.setRequestHeader(entry[0], encodeURI(entry[1])));
			if (!ARG.nojson) request.responseType = 'json';
			//二进制上传
			request.send(ARG.body);
		});
	}
	/**
	 * 
	 * @param {[...File]} files
	 * @returns {Promise<[File,Number,Number]>} 返回
	 */
	async toZip(files, progress) {
		if (!self.zip) {
			await import(await this.importLink(this.zip_js, str => progress && progress('zipjs已下载:' + str)));
		}
		const mime = 'application/x-zip-compressed';
		const zip = self.zip;
		//为了兼容QQ浏览器 采用Uint8ArrayWriter
		const zipFileWriter = new zip.Uint8ArrayWriter(mime);
		const writer = new zip.ZipWriter(zipFileWriter);
		let length = 0;
		await Promise.all(Array.from(files,
			function (file) {
				if (file instanceof File&&file.size) {
					length += 1;
					return writer.add(file.name, new zip.BlobReader(file), {
						onprogress: (start, end) => progress && progress(Math.ceil(start * 100 / end), file.name)
					});
				}
			}
		));
		await writer.close();
		if(length==0) return [0,0,0];
		const data = new Uint8Array(await zipFileWriter.getData());
		return [new File([data], files[0].name.split('.').slice(0, -1).join('.') + '.zip', {
			type: mime
		}), 0, 0];
	}
	/**
	 * 
	 * @param {File} file 
	 * @returns {Promise<File,Number,Number>} 返回
	 */
	async toImage(file, progress) {
		if(!file||(file instanceof File&&!file.size)){
			return [0,0,0];
		}
		/**  压缩图片函数 没水印功能  */
		const maxwith = 3840;
		const maxHeight = 3840;
		//此方法可以把图片缩放到 maxwith 内
		const imgbit = await createImageBitmap(file, {
			width: maxwith,
			height: maxHeight
		}).catch(e => false);
		if (!imgbit) {
			//不是合法图片 如SVG
			return this.toZip([file], (status, name) => toast.msg(name + ' 文件压缩:' + status + '%'));
		}
		//获取大小
		const {
			width,
			height
		} = imgbit;
		/** gif/webp 不压缩  压缩gif当前代价太大,二十多帧就要几秒以上,压缩率50%  */
		if (file.type == 'image/gif' || file.type == 'image/webp') {
			imgbit.close();
			return [file, width, height];
		} else {
			let filename = file.name.split('.').slice(0, -1);
			//强制压缩
			let filetype = 'image/webp';
			//检查 浏览器是否支持webp压缩 否则采用jpg压缩
			const mime = self.supportWebp || await (new Promise(back => document.createElement('canvas').toBlob(blob => back(blob.type), filetype)));
			let canvas = document.createElement('canvas');
			canvas.width = width;
			canvas.height = height;
			let ctx = canvas.getContext('2d', {
				alpha: true
			});
			ctx.drawImage(imgbit, 0, 0);
			imgbit.close();
			let newfile;
			if (mime != filetype) {
				if (self.WebAssembly) {
					if (!self.WasmWebp) {
						self.WasmWebp = this.loadwasm(progress);
					}
					const wasmwebp = await self.WasmWebp;
					if (wasmwebp) {
						const img = ctx.getImageData(0, 0, width, height);
						/**
						 * quality是图片质量 lossless好像是失真还是无损
						 */
						const result = wasmwebp.encode(img.data, width, height, true, {
							quality: 80,
							lossless: 0
						});
						return [new File([result], filename + '.webp', {
							type: 'image/webp'
						}), img.width, img.height];
					}
				}
				//强制压缩为jpg/png
				if (file.type != 'image/png') {
					filetype = 'image/jpg';
					newfile = await (new Promise(back => canvas.toBlob(blob => back(blob), filetype, 8)));
					filename += '.jpg';
				} else {
					//不压缩了
					ctx.reset();
					canvas.remove();
					return [file, width, height];
				}
			} else {
				newfile = await (new Promise(back => canvas.toBlob(blob => back(blob), filetype, 8)));
				filename += '.webp';
			}
			ctx.reset();
			canvas.remove();
			return [new File([newfile], filename, {
				type: filetype
			}), width, height];
		}
	}
	async upload(callback) {
		const E = this;
		const input = document.createElement('input');
		input.type = 'file';
		input.multiple = true;
		input.onchange = async function () {
			callback(this.files);
		};
		input.click();
		input.remove();
		return;
	}
	async file_upload(file, progress) {
		const E = this;
		let result;
		if(!file||(file instanceof File&&!file.size)){
			const errmsg = '上传失败,空文件!';
			if (progress instanceof Function) {
				throw errmsg;
			}
			E.alert(errmsg);
			return '';

		}
		const toast = E.toast.add('上传:' + file.name, '检查中');
		if (file.type.indexOf('image') !== -1) {
			result = await E.toImage(file, (status) => toast.msg(status));
		} else {
			//判断非图片 文件类型
			let arr = ['504b0304', '52617221', '377abcaf'];
			let buf = Array.from(new Uint8Array(await file.slice(0, 4).arrayBuffer()), e => e.toString(16).padStart(2, '0')).join('');
			if (arr.includes(buf)) {
				result = [file, 0, 0];
			} else {
				result = await E.toZip([file], (status, name) => toast.msg(name + ' 文件压缩:' + status + '%'));
			}
		}
		if (!result || !result[0]) {
			$(toast).toast('hide');
			const errmsg = '上传失败,空文件,或者文件无法处理!';
			if (progress instanceof Function) {
				throw errmsg;
			}
			E.alert(errmsg);
			return '';
		}
		toast.msg('检查完毕,准备上传');
		const response = await E.ajax({
			href: E.action,
			method: 'POST',
			body: await result[0].arrayBuffer(),
			headers: {
				'accept': 'attach/upload',
				'content-type': result[0].type,
				'content-name': result[0].name,
				'content-width': result[1],
				'content-height': result[2],
			},
			progress: (start, end) => {
				toast.msg('上传进度:' + Math.ceil(start * 100 / end) + '%');
				if (progress instanceof Function) {
					progress(Math.ceil(start * 100 / end));
				}
			}
		}).catch(e => false);
		$(toast).toast('hide');
		if (response && response.message && response.message.url) {
			const R = response.message;
			if (R.width) {
				if (progress instanceof Function) {
					return R.url;
				}
				E.insertContent('<br><img src="' + R.url + '" width="' + R.width + '" style="max-width:100%" title="' + R.orgfilename + '"/><br>');
			} else {
				E.insertContent('<p><a href="' + R.url + '" title="' + R.orgfilename + '">' + R.orgfilename + '</a></p>');
				if (progress instanceof Function) {
					throw '这不是图片,但我替你压缩了~';
				}
			}
		} else {
			const errmsg = response && response.message || '上传失败,服务器错误!';
			if (progress instanceof Function) {
				throw errmsg;
			}
			E.alert(errmsg);
		}

	}

	async showattach(event, editor) {
		const E = this;
		const response = await E.ajax({
			href: E.action,
			method: 'POST',
			headers: {
				'accept': 'attach/list',
				'content-pid': E.pid,
			}
		});
		if (typeof response.message == 'string') {
			E.alert(response.message);
		} else {
			let list = response.message;
			if (!list.push) list = Object.values(list);
			if (list.length == 0) return E.alert('你没有附件上传');
			let html = '<ul>';
			for (let item of list) {
				let prex = /^\d+$/.test(item['aid']) ? '[已使用附件]' : '[临时附件]';
				html += `<li style="display: flex;justify-content: space-between;align-items: center;flex-direction: row;font-size: .675rem;width: 100%;overflow: hidden;flex-wrap: wrap;margin-bottom:.5rem"><p style="width:100%;margin:0px;">${prex}${item['filename']}</p><button type="button" class="btn btn-primary" data-bs-dismiss="modal" data-type="add" data-url="${item['url']}" data-ext="${item['filetype']}" data-name="${item['filename']}" style="font-size: .675rem;">插入</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-type="del" data-aid="${item['aid']}" style="font-size: .675rem;">删除</button></li>`
			}
			html += '</ul>';
			E.attachModal(html);
		}
	}
	attachModal(html) {
		const E = this;
		if (!this.attach_modal) {
			this.attach_modal = this.createModal();
			$('.modal-body', this.attach_modal).on('click', async function (event) {
				const type = event.target.getAttribute('data-type');
				if (type == 'add') {
					const url = event.target.getAttribute('data-url');
					const ext = event.target.getAttribute('data-ext');
					const orgfilename = event.target.getAttribute('data-name');
					if (ext == 'image') {
						E.insertContent('<br><img src="' + url + '" width="50%" title="' + orgfilename + '"/><br>');
					} else {
						E.insertContent('<a href="' + url + '">' + orgfilename + '</a>');
					}
					E.attach_modal.modal('hide');

				} else if (type == 'del') {
					const aid = event.target.getAttribute('data-aid');
					//E.attach_modal.modal('hide');
					await E.delAttach(aid);
					//不隐藏 删除元素代替
					event.target.parentNode.remove();
				}
			});
			this.attach_modal.on('html',
				/**
				 * 
				 * @param {CustomEvent} event 
				 */
				function (event) {
					console.log(event);
					$(this).modal('show');
				});
		}
		$('.modal-body', this.attach_modal)[0].innerHTML = html;
		this.attach_modal.modal('show');
	}
	createModal() {
		const Modal = document.createElement('div');
		Modal.className = "modal fade";
		Modal.innerHTML = `<div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-body"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button><button type="button" class="btn btn-primary ok" hidden>确定</button></div></div></div>`;
		document.body.appendChild(Modal);
		return $(Modal).modal({
			backdrop: 'static',
			focus: false
		});
	}
	async delAttach(aid) {
		const E = this;
		const response = await E.ajax({
			href: E.action,
			method: 'POST',
			headers: {
				'accept': 'attach/delete',
				'content-aid': aid
			}
		});
		if (response && response.message) {
			E.alert(response.message);
		} else {
			E.alert('删除失败');
		}
	}
	alert(...arg) {
		return this.tinymce.windowManager.alert(...arg);
	}
	confirm(...arg) {
		return this.tinymce.windowManager.confirm(...arg);
	}
	
	insertContent(...arg) {
		return this.tinymce.insertContent(...arg);
	}
	content(content) {
		if (content) {
			return this.tinymce.setContent(content);
		} else {
			return this.tinymce.getContent();
		}
	}
	doctype() {
		const elm = this.form.querySelector('[name="doctype"]');
		if (elm.value == '1') {
			return 1;
		}
		return 0;
	}
	IconList = {
		'insertImg': '<svg class="icon" style="width: 1em;height: 1em;vertical-align: middle;fill: currentColor;overflow: hidden;" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="6222"><path d="M915.084916 719.854978 810.23287 719.854978 810.23287 614.985536c0-23.303752-17.479093-40.782845-40.782845-40.782845s-40.782845 17.479093-40.782845 40.782845l0 110.696148L612.144326 725.681684c-23.303752 0-40.782845 17.479093-40.782845 40.782845s17.479093 40.782845 40.782845 40.782845l110.696148 0 0 110.678752c0 23.303752 17.479093 40.782845 40.782845 40.782845s40.782845-17.479093 40.782845-40.782845L804.406165 807.247374 915.084916 807.247374c23.304775 0 40.782845-17.47807 40.782845-40.782845C961.694467 743.160777 938.390715 719.854978 915.084916 719.854978L915.084916 719.854978 915.084916 719.854978zM506.427586 825.749774 506.427586 825.749774 141.658835 825.749774c-13.524015 0-23.675212-10.151197-23.675212-23.710005L117.983623 139.732461c0-13.533224 10.151197-23.684422 23.675212-23.684422l646.381593 0c13.505595 0 23.657816 10.151197 23.657816 23.684422l0 357.988324 0 0 0 0c0 13.524015 10.169617 23.676236 23.711028 23.676236 13.522991 0 23.675212-10.152221 23.675212-23.676236l0 0 0 0L859.084485 116.049063c0-27.073612-23.675212-50.759057-50.758034-50.759057L117.9826 65.290005c-27.065426 0-50.758034 23.685445-50.758034 50.759057L67.224566 825.749774c0 27.048029 23.692608 50.740638 50.758034 50.740638L506.427586 876.490412l0 0c13.522991 0 23.675212-10.169617 23.675212-23.692608C530.101775 835.883575 519.949554 825.749774 506.427586 825.749774L506.427586 825.749774 506.427586 825.749774zM501.447155 708.202591c17.479093-34.95614 58.261938-58.261938 99.044784-58.261938l46.608527 0 0-52.43421c0-46.609551 29.131481-87.392396 69.913303-104.870466l0 0 0 0-58.260915-174.774559L454.838627 580.028373l-58.261938-93.218078L181.028495 708.202591 501.447155 708.202591 501.447155 708.202591 501.447155 708.202591zM396.577712 335.331301c0-34.94693-29.130458-64.078411-64.086597-64.078411s-64.087621 29.131481-64.087621 64.078411c0 34.957163 29.130458 64.087621 64.087621 64.087621S396.577712 370.288464 396.577712 335.331301L396.577712 335.331301 396.577712 335.331301zM396.577712 335.331301" fill="#272636" p-id="6223"></path></svg>',

		'attachList': '<svg class="icon" style="width: 1.076171875em;height: 1em;vertical-align: middle;fill: currentColor;overflow: hidden;" viewBox="0 0 1102 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="11529"><path d="M966.031169 224.754385L762.25188 21.258516a70.571687 70.571687 0 0 0-51.015678-21.256532 70.855108 70.855108 0 0 0-51.015677 21.539953l-139.442852 141.710215L595.317246 234.957521l115.918956-118.75316 160.132543 159.849122L276.185841 878.605319l-160.415964-159.565702 297.874873-301.275918 37.128076 37.128076-163.533588 163.250168 73.122471 73.122471 185.073541-185.356961a71.988789 71.988789 0 0 0 0-102.031355l-59.51829-59.518291-36.561236-36.277815a51.582518 51.582518 0 0 0-73.122471 0L20.824033 668.59078a72.55563 72.55563 0 0 0 0 101.747935L226.870686 974.684845a71.705369 71.705369 0 0 0 51.015678 20.973112 70.855108 70.855108 0 0 0 51.015677-21.539953l637.412549-647.615684a72.55563 72.55563 0 0 0-0.283421-101.747935zM894.892641 585.095121l206.330074-2.125653 1.076997 103.165037-206.330073 2.097311zM795.27036 755.28909l304.676963-3.060941 1.048655 103.165037-304.676963 3.06094zM688.364173 920.976673l413.510409-1.417102 0.368446 103.165037-413.510408 1.417102z" fill="#7D7D7D" p-id="11530"></path></svg>',

		'attachupload': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M14.8287 7.7574L9.1718 13.4143C8.78127 13.8048 8.78127 14.4379 9.1718 14.8285C9.56232 15.219 10.1955 15.219 10.586 14.8285L16.2429 9.17161C17.4144 8.00004 17.4144 6.10055 16.2429 4.92897C15.0713 3.7574 13.1718 3.7574 12.0002 4.92897L6.34337 10.5858C4.39075 12.5384 4.39075 15.7043 6.34337 17.6569C8.29599 19.6095 11.4618 19.6095 13.4144 17.6569L19.0713 12L20.4855 13.4143L14.8287 19.0711C12.095 21.8048 7.66283 21.8048 4.92916 19.0711C2.19549 16.3374 2.19549 11.9053 4.92916 9.17161L10.586 3.51476C12.5386 1.56214 15.7045 1.56214 17.6571 3.51476C19.6097 5.46738 19.6097 8.63321 17.6571 10.5858L12.0002 16.2427C10.8287 17.4143 8.92916 17.4143 7.75759 16.2427C6.58601 15.0711 6.58601 13.1716 7.75759 12L13.4144 6.34319L14.8287 7.7574Z"></path></svg>'
	};
}
export class mytoast {
	constructor() {
		const elm = document.createElement('div');
		elm.id = 'editot-toast-status';
		elm.style.cssText = `
				display: flex;
				flex-flow: column-reverse;
				align-items: flex-end;
				justify-content: flex-start;
				position: fixed;
				right: 0px;
				bottom: 0px;
				width: min(300px, 45vw);
				z-index: 99990;
				pointer-events: none;`;
		elm.hidden = true;
		this.elm = elm;
		document.body.appendChild(elm);
	}
	add(title, message, time) {
		const T = this;
		const t = this.createtoast(title, message);
		this.appendChild(t);
		this.elm.hidden = false;
		$(t).toast({
			autohide: time ? true : false,
			delay: time || 0,
		}).on('hidden.bs.toast', function (event) {
			this.remove();
			T.remove();
		}).toast('show');
		t.msg = function (msg) {
			this.querySelector('.msg').innerHTML = msg;

		}
		return t;
	}
	remove() {
		if (!this.elm.children.length) {
			this.elm.hidden = true;
		}
	}
	appendChild(elm) {
		return this.elm.appendChild(elm);
	}
	hidden(bool) {
		this.elm.hidden = bool;
	}
	createtoast(title, message) {
		const elm = document.createElement('div');
		elm.setAttribute('class', 'toast text-center mb-2');
		let html = '';
		if (message) {
			html += `<div class="toast-header p-2"><svg class="bd-placeholder-img rounded me-2" width="20" height="20" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="#007aff"></rect></svg><strong style="text-align: left;word-break: keep-all;text-overflow: ellipsis;overflow: hidden;flex-grow:1;font-size:.675rem">${title}</strong><button type="button" class="btn-close m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
			html += `<div class="toast-body msg p-2">${message}</div>`;
		} else {
			html += `<div style="display: flex;align-items: center;"><div class="toast-body msg p-2" style="flex-grow: 1;">${title}</div><button type="button" class="btn-close p-2" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
		}
		elm.innerHTML = html;
		return elm;
	}
};