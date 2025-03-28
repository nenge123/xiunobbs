export default {
	response_error(type) {
		switch (true) {
			case type >= 500:
				$.alert(lang['http_error_500']);
				break;
			case type == 404:
				$.alert(lang['http_error_404']);
				break;
			case type == 403:
				$.alert(lang['http_error_403']);
				break;
			case type == 0:
			case type > 400:
				$.alert(lang['http_error_lose']);
				break;
		}
	},
	webpsupport() {
		this.methods.set('webpsupport', document.createElement('canvas').toDataURL('image/webp').match(/image\/\w+/)[0]);
		return this.callMethod('webpsupport');
	},
	async webpConver() {
		const wasmwebp = await (await import(this.webp_wasm_path + 'webp-wasm.js')).default({ wasmBinary: new Uint8Array(await (await fetch(this.webp_wasm_path + 'webp-wasm.wasm')).arrayBuffer()) })
		await wasmwebp.ready;
		delete wasmwebp.wasmBinary;
		this.methods.set('webpConver', wasmwebp);
		return wasmwebp;
	},
	async imagedata2webp(imagedata) {
		const wasmwebp = await this.callMethod('webpConver');
		/**
		 * quality是图片质量 lossless好像是失真还是无损
		 */
		const result = wasmwebp.encode(imagedata.data, imagedata.width, imagedata.height, true, {
			quality: 80,
			lossless: 0
		});
		return new Uint8Array(result);

	},
	async canvas2blob(canvas, type) {
		return new Promise(back => canvas.toBlob(blob => back(blob), type, 8))
	},
	/**
	 * 水印 
	 */
	canvasWaterText(ctx) {
		ctx.save();
		ctx.font = '24px STheiti, SimHei';
		ctx.lineWidth = 3;
		ctx.strokeStyle = '#00000080';
		let w = ctx.measureText(this.watertext).width;
		ctx.strokeText(this.watertext, width - w.width, height - 24, maxwith / 2);
		ctx.fillStyle = '#ffffff80';
		ctx.fillText(this.watertext, width - w.width, height - 24, maxwith / 2);
		ctx.save();
	},
	/**
	 * 格式化一张图片并添加水印,优先webp/jpg/png,对gif不予处理
	 */
	async formatImage(file, maxwith, maxHeight) {
		if (file.type == 'image/gif') return file;
		let filename = file.name.split('.').slice(0, -1);
		let filetype = 'image/webp';
		maxwith = maxwith || 3840;
		maxHeight = maxHeight || 3840;
		const imgbit = await createImageBitmap(file, { width: maxwith, height: maxHeight });
		const { width, height } = imgbit;
		const canvas = document.createElement('canvas');
		canvas.width = width;
		canvas.height = height;
		const ctx = canvas.getContext('2d', { alpha: true });
		ctx.drawImage(imgbit, 0, 0);
		if (this.watertext) {
			//文字水印
			this.callMethod('canvasWaterText', ctx);
		}
		const mime = this.callMethod('webpsupport');
		if (mime != filetype) {
			//不支持webp
			if (self.WebAssembly) {
				const result = await this.callMethod('imagedata2webp', ctx.getImageData(0, 0, width, height)).catch(e => false);
				if (result) {
					return new File([result], filename + '.webp', { type: 'image/webp' });

				}
			}
			if (file.type != mime) {
				filetype = 'image/jpg';
				filename += '.jpg';
			} else {
				filetype = mime;
				filename += '.png';
			}
		} else {
			filename += '.webp';
		}
		const newfile = new File([await this.callMethod('canvas2blob', canvas, filetype)], filename, { type: filetype });
		canvas.remove();
		return newfile;
	},
	/**
	 * 压缩一个文件
	 */
	async formatZip(files,progress) {
		const X = this;
		if (!self.zip) {
			X.callMethod('zip_progress',{text:'加载压缩程序!'})
			await import(X.zip_js);
		}
		const type = 'application/x-zip-compressed';
		const zip = self.zip;
		const zipFileWriter = new zip.Uint8ArrayWriter(type);
		const writer = new zip.ZipWriter(zipFileWriter);
		let length = 0;
		for (const file of files) {
			if (file instanceof File && file.size) {
				X.callMethod('zip_progress',{text:'压缩文件:' + file.name});
				await writer.add(file.name, new zip.BlobReader(file), {onprogress:(start, end)=>X.callMethod('zip_progress',{progress:Math.floor(100*start/end)})});
				length++;
			}
		}
		await writer.close(new TextEncoder().encode('能哥网 nenge.net', {
			onprogress(start, end, entry) {
				X.callMethod('zip_progress',{text:'压缩文件:' + entry.filename});
				X.callMethod('zip_progress',{progress:Math.floor(100*start/end)})
			}
		}));
		if(!length) throw 'empty file';
		const data = new Uint8Array(await zipFileWriter.getData());
		return new File([data], files[0].name.split('.').slice(0, -1).join('.') + '.zip', { type });
	},
	async upload(callback, accept,multiple) {
		const input = document.createElement('input');
		input.type = 'file';
		if (accept) input.accept = accept;
		input.multiple = multiple?true:false;
		input.once('change', async function () {
			const files = Array.from(this.files).filter(file => (file instanceof File) && file.size > 0);
			if(files.length)callback(files);
		});
		input.click();
		input.remove();
	},
	async FormData(...arg){
		const FormData = self.FormData;
		if (!FormData || !FormData.prototype || !FormData.prototype.set || !FormData.prototype.entries) {
			/** 还原QQ浏览器篡改内置对象 */
			await import(this.form_js);
		}
		return new self.FormData(...arg);
	},
	async admin_forum_edit(elm){
		const X = this;
		$(elm).find('input').one('click',async function(){
			const {editor} = await import(X.admin_jsroot+'tinymce.js');
			const p = $(elm).next();
			const E = new editor(p[0]);
			$(this).prop('disabled',true);
			//E.name = $(elm).next().attr('name');
			E.form = this.form;
		});
	},
	adimin_thread_delete(elm){
		elm.on('click',function(){
			if(this.disabled) return;
			this.disabled = true;
			let tids = Array.from(document.querySelectorAll('[name^=tids]')).filter(e=>e.checked).map(e=>e.value).join(',');
			let url = this.form.getAttribute('action');
			url += (url.indexOf('?')==-1 ? '?':'&')+'tids='+tids;
			const E = document.querySelector('#delete-result');
			E.style.cssText = 'white-space: pre;';
			if(	tids){
				X.callMethod('createEventSource',url,{
					open(event){
						console.log(event);
						if(event.data){
							let p = JSON.parse(event.data);
							p&&p.message&&E.append(p.message+"\n");
						}
					},
					progress(event){
						console.log(event);
						if(event.data){
							let p = JSON.parse(event.data);
							if(p){
								if(p.message){
									E.append(p.message+"\n");
								}
								if(p.tid){
									document.querySelector('#tid-'+p.tid).remove();
								}
							}
						}
					},
					close(event){
						if(event.data){
							let p = JSON.parse(event.data);
							if(p){
								p&&p.message&&E.append(p.message+"\n");
								if(p.url){
									$(E).delay(3000).location(p.url);
								}
							}
		
						}
					}
				});
			}
			this.disabled = false;
		})
	},
	get_form_elm(elm){
		const X = this;
		if(X.isJQ(elm)){
			if(X.isFrom(elm[0])){
				return elm[0];
			}
			elm = elm[0];
		}else if(X.isFrom(elm)){
			return elm;
		}
		if(elm.from&&X.isFrom(elm.from)){
			return elm.from;
		}
		return false;
	},
	scrollView(id){
		const elm = document.querySelector(id);
		if(elm instanceof HTMLElement){
			elm.scrollIntoView();
		}
	},
	createEventSource(url,opt){
		const link = new EventSource(url);
		if(opt){
			for(const e in opt){
				link.on(e,opt[e]);
			}
		}
		link.once('close',function(){this.close()});
		return link;
	},
	checkall(elm){
		elm.on('click',function(event){
			const lists = document.querySelectorAll(this.getAttribute('data-match'));
			const bool = !lists[0].checked;
			lists.forEach(e=>e.checked=bool);
		});
	}
}