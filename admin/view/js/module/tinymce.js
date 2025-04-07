export class editor {
	cdn_unpkg_path = 'https://lf6-unpkg.zstaticcdn.com/';
	webp_wasm_path = this.cdn_unpkg_path + 'wasm-webp@0.0.2/dist/esm/';
	wasm_js = this.webp_wasm_path+'webp-wasm.js';
	wasm_buf = this.webp_wasm_path+'webp-wasm.wasm';
	zip_js = this.cdn_unpkg_path + '@zip.js/zip.js@2.7.53/dist/zip.min.js';
	form_js = this.cdn_unpkg_path + 'formdata-polyfill@4.0.10/formdata.min.js';
	//skin_url = 'https://cdn.tiny.cloud/1/qagffr3pkuv17a8on1afax661irst1hbr4e6tbv888sz91jc/tinymce/7.7.1-150/skins/ui/fluent';
	csstext = [
		'.tox .tox-menu{z-index:99999999999;}',
		'.tox-statusbar .tox-statusbar__branding{display:none !important;}',
		'.tox-tinymce{border-width:1px}'
	];
	datas = new Map;
	constructor(elm) {
		this.elm = elm;
		this.form = elm.form;
		this.name = elm.getAttribute('name');
		elm.removeAttribute('name');
		this.init();
	}
	async init() {
		const E = this;
		const tinyMCE = await X.callMethod('tinymce7');
		tinyMCE.init({
			target: this.elm,
			toolbar: ['myattach viewattach savedata'], // 界面按钮
			image_urlconvertor_callback(url){
				console.log(url);
				return url.replace(/^[\.\/]+\/(.+?)\/forum\//ig,'/$1/forum/');
			},
			async images_upload_handler(blobInfo, progress, failure) {
				return new Promise((back, err) => {
					const request = E.createPOST('upload', function (result) {
						if (result) {
							if (result.url) {
								return back(result.url);
							} else {
								err(result.message);
							}
						}else{
							err('上传失败');
						}

					}, function (str, type) {
						if (type == 'up') {
							progress && progress(str);
						}
					});
					const post = new FormData;
					post.set('file', blobInfo.blob());
					request.send(post);
				});
			},
			setup: (editor) => this.setup(editor),
			save_onsavecallback(editor){
				console.log('Save canceled',editor,this);
				const post = new FormData;
				//console.log(editor.save());
				let html = editor.getContent();
				html = html.replace(/"[\.\/]+\/(.+?)\/forum\//ig,'"/$1/forum/');
				post.set(E.name, html);
				const request = E.createAjax(function (response) {
					E.execAlert(response.message);
					editor.dispatch('save',true);
					//const savedata =  editor.ui.registry.getAll().buttons['savedata'];
					//savedata.onSetup();

				});
				request.open('POST', E.form.action);
				request.responseType = 'json';
				request.send(post);
			}
		});
	}
	setup(editor) {
		const E = this;
		E.editor = editor;
		console.log(editor);
		editor.ui.registry.addButton('viewattach', {
			icon:'gallery',
			text: '附件列表',
			onAction() {
				E.attachShowPanel();
			}
		});
		console.log(editor.ui.registry.getAll().buttons['savedata']);
		editor.ui.registry.addButton('savedata', {
			icon: 'lock',
			text: '保存内容',
			enabled:false,
			onAction(...arg) {
				editor.execCommand('mceSave');
			},
			onSetup(api){
				editor.once('keyup change',()=>api.setEnabled(true));
				editor.on('save',()=>{
					editor.once('keyup change',()=>api.setEnabled(true));
					api.setEnabled(false);
				});
			}
		});
		editor.ui.registry.addButton('myattach', {
			icon: 'upload',
			text: '附件上传',
			onAction() {
				E.attachUpload(()=>E.attachFetchList());
			}
		});

	}
	itemAdd(id) {
		const E = this;
		const { link, title, bool } = E.attachItem(id);
		E.execInsert(link, title, bool);
	}
	itemRemove(id) {
		const E = this;
		const { link, title, bool } = E.attachItem(id);
		E.contentRemove(link, title, bool);
	}
	imageExt = ['webp', 'png', 'jpg', 'gif', 'apng', 'avif', 'jpeg'];
	itemToggle(id) {
		if (!id || id == '-1') return;
		const E = this;
		const { link, title, bool } = E.attachItem(id);
		var image = E.contentFind(link, title, bool);
		if (image.length) image.forEach(e => E.execRemove(e));
		else E.execInsert(link, title, bool);
	}
	itemClear(id) {
		const E = this;
		E.execConfirm('删除不可恢复,确定吗?', state => {
			if (state) {
				const { link, title, bool } = E.attachItem(id);
				const post = new FormData;
				post.set('name', title);
				E.datas.get('attachDialog').close();
				E.attachFetchData('delete', data => {
					E.contentRemove(link, title, bool);
					E.attachSetData(data);
					E.attachShowPanel();
				}, post);
			}
		});
	}
	contentFind(link, title, bool) {
		let ext = link.split('.').pop();
		if (ext == 'gif') {
			if (this.contentIsVideo(link)) {
				return this.editor.dom.select(`video[poster="${link}"]`);

			}
		} else if (/\.(webm|mp4)$/.test(link)) {
			return Array.from(this.editor.dom.select(`video source[src*="${link}"]`), e => e.parentNode);
		}
		return this.editor.dom.select(bool ? `a[href="${link}"]` : `img[src="${link}"]`);
	}
	contentIsVideo(link) {
		const name = link.split('.').slice(0, -1).join('.');
		for (let data of this.attachData()) {
			if (/\.(webm|mp4)$/.test(data.link) && data.link.includes(name)) {
				return data.link;
			}
		}
		return false;
	}
	contentRemove(link, title, bool) {
		return Array.from(this.contentFind(link, title, bool), elm => this.execRemove(elm));
	}
	execInsert(link, title, bool) {
		const E = this;
		const editor = this.editor;
		let ext = link.split('.').pop();
		if (ext == 'gif') {
			const file = this.contentIsVideo(link);
			if (file) {
				return editor.insertContent(`<video poster="${link}" preload="metadata" playsinline="true" muted="true" controls><source src="${file}" type="video/${file.split('.').pop()}" /></video>`);
			}
		} else if (/\.(webm|mp4)$/.test(link)) {
			return editor.insertContent(`<video alt="${title}" preload="metadata" playsinline="true" muted="true" controls><source src="${link}" type="video/mp4" /></video>`);
		} else if (ext == 'video') {
			return this.execConfirm('视频文件属性未知,是否进行转码mp4?', state => {
				if (state) {
					const winalert = E.execAlert('确定后,转换后台启动,请勿关闭浏览器,你可以继续编辑.转换后将会返回提示!', () => {
						E.datas.get('attachDialog').close();
						const request = E.createPOST('convert', function (result) {
							if (result) {
								if (result.message) {
									E.execAlert(result.message);
								} else {
									E.attachFetchList();
								}
							} else {

								E.execAlert('转换失败');
							}
						});
						const post = new FormData;
						post.set('name', link.split('.').slice(0, -1).join('.'));
						request.send(post);
					});
				}
			});
		}
		editor.insertContent(bool ? `<a href="${link}">${title}</a>` : `<img src="${link}" title="${title}" width="100%">`);
	}
	execNotice(text) {
		return this.editor.notificationManager.open({
			text,
			type: 'success',
			Icon: 'upload',
			progressBar: true
		});
	}
	execRemove(elm) {
		return this.editor.execCommand('mceRemoveNode', false, elm);
	}
	execDialog(title, items, options) {
		options = options || {};
		options.title = title;
		if (!options.size) options.size = 'small';
		options.body = {
			type: 'panel',
			items,
		};
		return this.editor.windowManager.open(options);
	}
	execAlert(message, fn) {
		return this.editor.windowManager.alert(message, fn);
	}
	execConfirm(message, fn) {
		return this.editor.windowManager.confirm(message, fn);
	}
	execActive(bool) {
		this.editor.setProgressState(bool);
	}
	createAddItem(k, bool) {
		return this.createMenuItem(bool ? 'link' : 'image', '插入', () => this.itemAdd(k, bool));
	}
	createRemoveItem(k, bool) {
		return this.createMenuItem(bool ? 'link' : 'image', '移除', () => this.itemRemove(k, bool));
	}
	createClearItem(k, bool) {
		return this.createMenuItem('remove', '删除', () => this.itemClear(k, bool));
	}
	createMenuItem(icon, text, onAction) {
		return { icon: icon || 'remove', type: 'menuitem', text, onAction };
	}
	createFetchItem(id, bool) {
		const E = this;
		return function (success) {
			return success([E.createAddItem(id, bool), E.createRemoveItem(id, bool), E.createClearItem(id, bool)])
		};
	}
	async attachFetchList() {
		const E = this;
		E.attachFetchData('list', data => {
			E.attachSetData(data);
			E.attachShowPanel();
		}, null);
	}
	attachFetchData(action, fn, post) {
		const E = this;
		const editor = this.editor;
		let windiv;;
		let filename;
		if (post && post.has('file')) {
			filename = post.get('file').name;
			windiv = E.execNotice('上传文件:' + filename);
		} else {
			windiv = this.attachLoading();
		}
		const request = E.createPOST(action, function (result) {
			windiv.close();
			if (result) {
				if (result.data) {
					return fn(result.data);
				}
				fn();
				return E.execAlert(result.message);
			}
			fn();
			E.execAlert('请求异常');
		}, function (str, type) {
			if (type == 'up') {
				windiv.progressBar && windiv.progressBar.value(str);
			}
		});
		request.send(post || null);
	}
	attachItem(id) {
		return this.attachData()[id];
	}
	attachData() {
		return this.datas.get('attachlist');
	}
	attachSetData(data) {
		if(!data) return;
		this.datas.set('attachlist', Array.from(data, link => {
			const title = link.split('/').pop();
			const ext = title.split('.').pop();
			const bool = !this.imageExt.includes(ext.toLowerCase());
			return {
				link,
				title,
				ext,
				bool,
			}
		}) || []);
	}
	async attachShowPanel() {
		const E = this;
		const editor = this.editor;
		/**
		 * @type Array data
		 */
		const data = E.attachData();
		if (!data) {
			return E.attachFetchList();
		}
		const items = data.map((value, id) => {
			const { link, title, bool } = value;
			return {
				type: 'leaf',
				title,
				id: id + '',
				menu: {
					icon: 'image',
					type: 'menubutton',
					fetch: E.createFetchItem(id, bool),
				}
			};
		});
		if (!items.length) {
			items.push({ type: 'leaf', title: '没有可用附件', id: '-1' });
		}
		E.datas.set('attachDialog', E.execDialog(
			'附件列表',
			[
				{
					type: 'tree',
					onLeafAction: id => E.itemToggle(id),
					items
				}
			],
			{
				buttons: [
					{
						type: 'cancel',
						icon: 'close',
						text: '关闭',
						buttonType: 'secondary',
						align: 'end',
					},
					{
						type: 'custom',
						icon: 'reload',
						name: 'refresh',
						text: '刷新',
						buttonType: 'primary',
						align: 'start',
					},
					{
						type: 'custom',
						icon: 'upload',
						text: '上传',
						name: 'upload',
						buttonType: 'primary',
						align: 'start',
					},
					/*
					{
						type: 'custom',
						icon: 'embed',
						text: '视频',
						name: 'video',
						buttonType: 'primary',
						align: 'start',
					}
					*/
				],
				onAction(dialog, option) {
					switch (option.name) {
						case 'refresh':
							dialog.close();
							E.datas.delete('attachDialog');
							E.attachFetchList();
							break;
						case 'upload':
							dialog.close();
							E.datas.delete('attachDialog');
							E.attachUpload(() => {
								E.attachFetchList();
							});
							break;
						case 'video':
							dialog.close();
							E.datas.delete('attachDialog');
							E.upload(async files => {
								for (const file of files) {
									await E.attachFile(file);
								}
								E.attachFetchList();
							}, 'video/*',false)
							break;
					}
				}
			},
		));
	}
	attachLoading() {
		return this.execDialog(
			'加载中',
			[
				{
					type: 'htmlpanel',
					html: '<div class="d-flex align-items-center justify-content-center flex-grow-1 gap-3"><div class="spinner-border text-danger" style="border: var(--bs-spinner-border-width) solid currentcolor;border-right-color: transparent;width: var(--bs-spinner-width);height: var(--bs-spinner-height);" role="status"></div>Loading...</div>'
				}
			],
		);
	}
	attachUpload(fn) {
		const E = this;
		const editor = this.editor;
		E.upload(async files => {
			if (files.length > 1) {
				E.execConfirm('你正在上传多个文件,是否逐个上传?否则会被压缩成zip上传!', async state => {
					if (state) {
						for (const file of files) {
							await E.attachPOST(file);
						}
						fn && fn();
					} else {
						const progress = E.execNotice('打包文件');
						const [file, width, height] = await this.toZip(files, progress);
						fn(await E.attachPOST(file, progress));
					}
				});
			}
			else {
				fn(await E.attachPOST(files[0]));
			}
		},undefined,true);
	}
	async attachPOST(file, progress, check) {
		const E = this;
		return new Promise(async fn => {
			if (!progress) progress = E.execNotice('检查:' + file.name);
			let width = 0, height = 0;
			if (!check) {
				if (file.type.indexOf('image') !== -1) {
					[file, width, height] = await this.toImage(file, progress);
				} else if (!(/(zip|rar|7z)/.test(file.type))) {
					let arr = ['504b0304', '52617221', '377abcaf'];
					let buf = Array.from(new Uint8Array(await file.slice(0, 4).arrayBuffer()), e => e.toString(16).padStart(2, '0')).join('');
					if (!arr.includes(buf)) {
						[file, width, height] = await this.toZip(file, progress);
					}
				}
			}
			progress.text('上传文件:' + file.name);
			const request = E.createPOST('upload', function (response) {
				progress.close();
				response && response.message && E.execAlert(response.message);
				fn(response);
			}, function (str, type) {
				if (type == 'up') progress.progressBar.value(str);
				else if (type == 'down') {
					progress.text('等待服务器回应:' + file.name);
					progress.progressBar.value(str);
				}
			});
			const post = new FormData;
			post.set('file', file);
			request.send(post);
		});
	}
	async attachFile(file) {
		const E = this;
		if (file.size > 2 * 1024 * 1024) {
			return E.attachBigFile(file);
		}
		return new Promise(back => {
			const post = new FormData;
			post.set('file', file);
			E.attachFetchData('upload', back, post)
		});
	}
	/**
	 * 
	 * @param {File} file 
	 */
	async attachBigFile(file) {
		const E = this;
		await import(import.meta.url.split('/').slice(0, -1).join('/') + '/spark-md5.min.js');
		const type = file.type;
		const filename = file.name;
		const size = file.size;
		const chunksize = 1.5 * 1024 * 1024;
		const progress = E.execNotice(`大文件上传:${filename}`);
		const maxsise = Math.ceil(size / chunksize);
		const md5func = new SparkMD5.ArrayBuffer();
		md5func.append(await file.arrayBuffer());
		const md5 = md5func.end();
		let page = 0;
		let name = '';
		let url;
		E.execActive(true);
		while (page < size) {
			let start = page * chunksize;
			let end = start + chunksize;
			if (end > size) end = size;
			const newfile = new File([file.slice(start, end)], filename, { type });
			progress.text(`大文件上传(${page + 1}/${maxsise}):${filename}`);
			const result = await E.createBIG(newfile, {
				size,
				pos: page,
				md5,
				name,
			}, function (str, type) {
				console.log(str);
				progress.progressBar.value(str);
			}).catch(e => false);
			if (!result) {
				break;
			} else if (result.message) {
				E.execAlert(result.message);
				break;
			} else if (result.url) {
				url = result.url;
				break;
			} else if (result.filename) {
				page += 1;
				name = result.filename;
			} else {
				break;
			}
		}
		progress.close();
		E.execActive(false);
		if (!url) {
			throw 'error';
		}
		return url;
	}
	async upload(callback, accept,multiple) {
		const E = this;
		const input = document.createElement('input');
		input.type = 'file';
		if (accept) input.accept = accept;
		input.multiple = multiple?true:false;
		input.addEventListener('change', async function () {
			this.files.length && callback(Array.from(this.files).filter(file => (file instanceof File) && file.size > 0));
		});
		input.click();
		input.remove();
	}
	createBIG(file, opt, progress) {
		const E = this;
		return new Promise(back => {
			const request = E.createPOST('big', result => back(result), progress);
			request.setRequestHeader('content-size', opt.size);
			request.setRequestHeader('content-pos', opt.pos);
			request.setRequestHeader('content-md5', opt.md5);
			request.setRequestHeader('content-name', opt.name || '');
			const post = new FormData;
			post.set('file', file);
			request.send(post);
		});
	}
	createPOST(action, success, progress) {
		const E = this;
		const request = E.createAjax(success, progress);
		request.open('POST', E.form.action);
		request.responseType = 'json';
		request.setRequestHeader('content-action', 'attach/' + action);
		request.setRequestHeader('ajax-fetch', '1');
		return request;
	}
	createAjax(success, progress, error) {
		const request = new XMLHttpRequest;
		if (progress instanceof Function) {
			request.addEventListener('progress', function (event) {
				progress(Math.floor(100 * event.loaded / event.total), 'down', event.loaded, event.total);
			});
			request.upload.addEventListener('progress', function (event) {
				progress(Math.floor(100 * event.loaded / event.total), 'up', event.loaded, event.total);
			});
		}
		request.addEventListener('readystatechange', function (event) {
			switch (this.readyState) {
				case this.DONE:
					success(this.response);
					break;
			}
		});
		request.addEventListener('error', function (event) {
			error(event.message || this.statusText);
		});
		return request;

	}
	async fetchLink(url, progress) {
		const table = this.table;
		let blob = await table.get(url);
		if (!blob) {
			blob = await this.fetchBlob(url, function (str, type) {
				if (progress instanceof Function) {
					if (type == 'down') progress(str);
				}
			});
			if (!blob) return url;
			await table.put(url, blob);
		}
		if (blob instanceof Blob) return URL.createObjectURL(blob);
		return url;
	}
	async fetchBlob(url, progress, type) {
		return new Promise((back, error) => {
			const request = this.createAjax(function (blob) {
				if (request.status == '200') {
					if (blob instanceof Blob) {
						const type = request.getResponseHeader('content-type');
						back(new File([blob], url.split('/').pop(), { type }));
					}
				}
				back(false);

			}, progress, err => back(false));
			request.open('GET', url);
			request.responseType = type ? type : 'blob';
			request.send(null);
		});
	}
	table = {
		async db() {
			return this.idb || (() => {
				this.idb = new Promise(back => {
					const req = self.indexedDB.open('cdn-files');
					req.addEventListener('upgradeneeded', function (event) {
						const db = event.target.result;
						db.createObjectStore('files');
					});
					req.addEventListener('success', function (event) {
						const db = event.target.result;
						return back(db);
					});
				});
				return this.idb;
			})()
		},
		async transaction() {
			const db = await this.db();
			const transaction = await db.transaction(['files'], "readwrite");
			return transaction.objectStore('files');
		},
		get(name) {
			return new Promise(async r => (await this.transaction()).get(name).onsuccess = e => r(e.target.result))
		},
		put(name, data) {
			return new Promise(async r => (await this.transaction()).put(data, name).onsuccess = e => r(e.target.result))
		}

	};
	async toZip(files, progress) {
		if (!self.zip) {
			progress.text('加载压缩程序!');
			await import(await this.fetchLink(this.zip_js, function (str, type) {
				if (type == 'down') progress.progressBar.value(str);
			}));
		}
		const type = 'application/x-zip-compressed';
		const zip = self.zip;
		const zipFileWriter = new zip.Uint8ArrayWriter(type);
		const writer = new zip.ZipWriter(zipFileWriter);
		let length = 0;
		for (const file of files) {
			if (file instanceof File && file.size) {
				progress.text('压缩文件:' + file.name);
				await writer.add(file.name, new zip.BlobReader(file), {
					onprogress(start, end) {
						progress.progressBar.value(Math.floor(start, end));
					}
				});
			}
		}
		await writer.close(new TextEncoder().encode('能哥网 nenge.net', {
			onprogress(start, end, entry) {
				progress.text(entry.filename);
				progress.progressBar.value(Math.floor(start, end));
			}
		}));
		const data = new Uint8Array(await zipFileWriter.getData());
		const newfile = new File([data], files[0].name.split('.').slice(0, -1).join('.') + '.zip', { type });
		return [newfile, 0, 0];
	}
	async toImage(file, progress) {
		const E = this;
		const maxwith = 3840;
		const maxHeight = 3840;
		const imgbit = await createImageBitmap(file, { width: maxwith, height: maxHeight }).catch(e => false);
		if (!imgbit) {
			return this.toZip([file], progress);
		}
		const { width, height } = imgbit;
		if (file.type == 'image/gif' || file.type == 'image/webp') {
			imgbit.close();
			return [file, width, height];
		} else {
			let filename = file.name.split('.').slice(0, -1);
			let filetype = 'image/webp';
			if (!self.supportWebp) {
				self.supportWebp = E.supportWebp();
			}
			const mime = await self.supportWebp;
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
					const result = await X.callMethod('imagedata2webp', ctx.getImageData(0, 0, width, height)).catch(e => false);
					if (result) {
						console.log(result);
						return [new File([result], filename + '.webp', { type: 'image/webp' }),width,height];
	
					}
				}
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
	supportWebp() {
		return new Promise(function (back) {
			const filetype = 'image/webp';
			document.createElement('canvas').toBlob(blob => back(blob.type == filetype ? filetype : false), filetype)
		})
	}
	async webpConver(progress) {
		const E = this;
		const table = this.table;
		progress.text('加载WebAssembly压缩');
		let wasmBinary = await E.fetchBlob(E.wasm_buf, function (str, type) {
			if (type == 'down') {
				progress.progressBar.value(str);
			}
		}, 'arraybuffer');
		if (!wasmBinary) {
			progress.text('加载WebAssembly失败!');
			return false;
		}
		progress.text('加载WebAssembly处理脚本!');
		await import(E.fetchLink(E.wasm_js, function (str, type) {
			if (type == 'down') {
				progress.progressBar.value(str);
			}
		}));
		wasmBinary = new Uint8Array(wasmBinary);
		const wasmwebp = await Module({
			wasmBinary,
			locateFile() {
				return E.wasm_buf;
			}
		});
		await wasmwebp.ready;
		wasmwebp.wasmBinary = null;
		delete wasmwebp.wasmBinary;
		progress.text('加载WebAssembly成功!');
		progress.progressBar.value('');
		return wasmwebp;
	}
}
document.querySelectorAll('[data-mode=tinymce]').forEach(elm => new editor(elm));