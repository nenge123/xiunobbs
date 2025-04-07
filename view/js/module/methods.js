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
	canvasWaterText(ctx, width, height) {
		ctx.save();
		ctx.font = '24px system-ui';
		ctx.lineWidth = 2;
		ctx.strokeStyle = '#00000090';
		let w = ctx.measureText(this.watertext).width;
		let b = w > width ? width / w : 1;
		if (w > width) {
			ctx.scale(width / w, width / w);
		}
		console.log(w);
		let x = w > width ? 0 : width - w;
		let y = (height - 24) / b;
		ctx.strokeText(this.watertext, x, y);
		ctx.fillStyle = '#ffffff90';
		ctx.fillText(this.watertext, x, y);
		ctx.closePath();
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
			this.callMethod('canvasWaterText', ctx, width, height);
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
	async formatZip(files, progress) {
		const X = this;
		if (!self.zip) {
			X.callMethod('zip_progress', { text: '加载压缩程序!' })
			await import(X.zip_js);
		}
		const type = 'application/x-zip-compressed';
		const zip = self.zip;
		const zipFileWriter = new zip.Uint8ArrayWriter(type);
		const writer = new zip.ZipWriter(zipFileWriter);
		let length = 0;
		for (const file of files) {
			if (file instanceof File && file.size) {
				X.callMethod('zip_progress', { text: '压缩文件:' + file.name });
				await writer.add(file.name, new zip.BlobReader(file), { onprogress: (start, end) => X.callMethod('zip_progress', { progress: Math.floor(100 * start / end) }) });
				length++;
			}
		}
		await writer.close(new TextEncoder().encode('能哥网 nenge.net', {
			onprogress(start, end, entry) {
				X.callMethod('zip_progress', { text: '压缩文件:' + entry.filename });
				X.callMethod('zip_progress', { progress: Math.floor(100 * start / end) })
			}
		}));
		if (!length) throw 'empty file';
		const data = new Uint8Array(await zipFileWriter.getData());
		return new File([data], files[0].name.split('.').slice(0, -1).join('.') + '.zip', { type });
	},
	async upload(callback, accept, multiple) {
		const input = document.createElement('input');
		input.type = 'file';
		if (accept) input.accept = accept;
		input.multiple = multiple ? true : false;
		input.once('change', async function () {
			const files = Array.from(this.files).filter(file => (file instanceof File) && file.size > 0);
			if (files.length) callback(files);
		});
		input.click();
		input.remove();
	},
	async FormData(...arg) {
		const FormData = self.FormData;
		if (!FormData || !FormData.prototype || !FormData.prototype.set || !FormData.prototype.entries) {
			/** 还原QQ浏览器篡改内置对象 */
			await import(this.form_js);
		}
		return new self.FormData(...arg);
	},
	get_form_elm(elm) {
		const X = this;
		if (X.isJQ(elm)) {
			if (X.isFrom(elm[0])) {
				return elm[0];
			}
			elm = elm[0];
		} else if (X.isFrom(elm)) {
			return elm;
		}
		if (elm.from && X.isFrom(elm.from)) {
			return elm.from;
		}
		return false;
	},
	scrollView(id) {
		if (id instanceof HTMLElement) {
			return elm.scrollIntoView();
		}
		if (id instanceof jQuery) {
			return elm[0].scrollIntoView();
		}
		const elm = document.querySelector(id);
		if (elm instanceof HTMLElement) {
			elm.scrollIntoView();
		}
	},
	createEventSource(url, opt) {
		const link = new EventSource(url);
		if (opt) {
			for (const e in opt) {
				link.on(e, opt[e]);
			}
		}
		link.once('close', function () { this.close() });
		return link;
	},
	checkall(elm) {
		elm.on('click', function (event) {
			const lists = document.querySelectorAll(this.getAttribute('data-match'));
			const bool = !lists[0].checked;
			lists.forEach(e => e.checked = bool);
		});
	},
	checkURL(url) {
		const surl = new URL(url, location.href);
		/** 必须是受信任域 */
		if (surl.origin !== location.origin && surl.origin !== this.vieworigin) {
			$.alert('对不起不允许执行站外代码!');
			return false;
		}
		return surl;

	},
	async loadmodule(elm) {
		const url = this.callMethod('checkURL', elm.getAttribute('module-url'));
		console.log(url);
		if (url) {
			const { default: Module } = await import(url.href);
			return new Module(elm, this);
		}
	},
	reload(elm) {
		$.reload(elm.getAttribute('href') || elm.getAttribute('url'), elm.getAttribute('seconds') || 2);
	},
	addJS(src) {
		const version = src.match(/\d[\d\.]+/ig);
		const id = ('script-' + (version?version[0]:'')+'-'+src.split('/').pop()).replace(/[\.\_]/ig, '-');
		if (document.querySelector('.' + id) instanceof HTMLScriptElement) {
			return true;
		}
		console.log(src);
		const script = document.body.appendChild(document.createElement('script'));
		script.src = src;
		script.type = 'text/javascript';
		script.classList.add(id);
		return new Promise(back => script.onload = e => back(e));
	},
	addCSS(src) {
		const version = src.match(/\d[\d\.]+/ig);
		const id = ('link-' + (version?version[0]:'')+'-'+src.split('/').pop()).replace(/[\.\_]/ig, '-');
		if (document.querySelector('.' + id) instanceof HTMLLinkElement) {
			return true;
		}
		console.log(src);
		const script = document.head.appendChild(document.createElement('link'));
		script.href = src;
		script.rel = 'stylesheet';
		script.type = 'text/css';
		script.classList.add(id);
		return new Promise(back => script.onload = e => back(e));
	},
	load_datetimepicker(){
		this.methods.set('load_datetimepicker',new Promise(async back=>{
			await this.callMethod('addJS', this.viewroot+'vendor/momentjs/moment.min.js');
			await this.callMethod('addJS', this.viewroot+'vendor/momentjs/locale/zh-cn.min.js');
			await import(this.viewroot+'vendor/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js');
			await this.callMethod('addCSS', this.viewroot+'vendor/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css');
			back(true);
			/**
			 * await this.callMethod('addJS', this.viewroot+'vendor/bootstrap-datepicker/bootstrap-datepicker.min.js');
			 * await this.callMethod('addJS', this.viewroot+'vendor/bootstrap-datepicker/locales/bootstrap-datepicker.zh-CN.min.js');
			 * await this.callMethod('addCSS', this.viewroot+'vendor/bootstrap-datepicker/bootstrap-datepicker3.min.css');
			 */
		}));
		return this.callMethod('load_datetimepicker');
	},
	async datetimepicker(elm) {
		await this.callMethod('load_datetimepicker');
        let options = $(elm).data();
		delete options.provide;
		console.log(options);
        $(elm).datetimepicker(options);
	},
	/**
	 * 加载一个兼容性良好的的tinymce5
	 * QQ浏览器亦可正常运行
	 * @param {*} path 
	 * @returns 
	 */
	tinymce5(path){
		const X = this;
		path = path || 'https://cdn.bootcdn.net/ajax/libs/tinymce/5.10.9/';
		//path = path || X.cdn_unpkg_path+'tinymce@5.10.9/';
		X.methods.set('tinymce5',new Promise(async function(back){
			const files = [
				'tinymce.min.js',
				X.isMobile ? 'themes/mobile/theme.min.js' : 'themes/silver/theme.min.js',
				'icons/default/icons.min.js',
			];
			const plugins = [
				"advlist", "anchor", "autolink", "autoresize", "autosave","charmap",
				"code", "codesample", "directionality",
				"fullscreen", "help", "hr",
				"image", //"imagetools",
				"insertdatetime", "link", "lists",
				"media", "nonbreaking", "noneditable", "pagebreak", "paste", "preview", "quickbars", "save", "searchreplace","tabfocus", "table","textpattern",
				"visualblocks", "visualchars",
				"wordcount"
			];
			for(let file of files){
				await import(path+file);
			}
			for(let file of plugins){
				await import(path+'plugins/'+file+'/plugin.min.js');
			}
			const tinyMCE = self.tinyMCE;
			const {default:LANG} = await import(X.jsroot+'module/tinymce/tinymce5_langs_'+X.i18n+'.js');
			tinyMCE.addI18n('en',LANG||{});
			back(X.callMethod('tinymce_init',tinyMCE,plugins,path));
		}));
		return X.methods.get('tinymce5');
	},
	/**
	 * 加载一个基础款的tinymce7
	 * @param {*} path 
	 * @returns 
	 */
	tinymce7(path){
		const X = this;
		path = path || 'https://cdn.bootcdn.net/ajax/libs/tinymce/7.6.1/';
		//path = path || X.cdn_unpkg_path+'tinymce@7.7.2/';
		X.methods.set('tinymce7',new Promise(async function(back){
			const files = [
				'tinymce.min.js',
				'themes/silver/theme.min.js',
				'icons/default/icons.min.js',
				'models/dom/model.min.js'
			];
			const plugins = [
				"accordion", "advlist", "anchor", "autolink", "autoresize", "autosave",
				"charmap", "code", "codesample", "directionality", "fullscreen", 
				"image", "importcss", "insertdatetime", "link", "lists", "media", 
				"nonbreaking", "pagebreak", "quickbars", "save", "searchreplace", 
				"table", "visualblocks", "visualchars", "wordcount"
			];
			for(let file of files){
				await import(path+file);
			}
			for(let file of plugins){
				await import(path+'plugins/'+file+'/plugin.min.js');
			}
			const tinyMCE = self.tinyMCE;
			const {default:LANG} = await import(X.jsroot+'module/tinymce/tinymce7_langs_'+X.i18n+'.js');
			tinyMCE.addI18n('en',LANG||{});
			back(X.callMethod('tinymce_init',tinyMCE,plugins,path));
		}));
		return X.methods.get('tinymce7');
	},
	/**
	 * 配置默认属性
	 */
	tinymce_init(tinyMCE,plugins,path){
		const X = this;
		tinyMCE.baseURL = path;
		tinyMCE.baseURI = new URL(path);
		tinyMCE.suffix ='.min';
		tinyMCE.documentBaseURL = new URL(X.webroot,location.href).href;
		tinyMCE.defaultOptions = {
			plugins,
			toolbar_mode: 'floating',
			promotion: false,
			license_key: 'gpl',
			contextmenu: false,
			content_style: "img{max-width:90%}video{object-fit: fill;}",
			paste_data_images: true, // 粘贴图片必须开启
			mobile: {
				menubar: true,
				toolbar_mode: 'floating',
			},
			quickbars_insert_toolbar: true,
			min_height: 400, // 最小高度
			max_height: 800,
			suffix:tinyMCE.suffix,
			image_default_type:'absolute',
			block_unsupported_drop: false,
			images_reuse_filename:true,
			base_url:path,
		};
		if(tinyMCE.overrideDefaults){
			tinyMCE.overrideDefaults(tinyMCE.defaultOptions);
		}
		document.body.appendChild(document.createElement('style')).innerHTML = '.tox .tox-menu{z-index:99999999999;}.tox-statusbar .tox-statusbar__branding{display:none !important;}.tox-tinymce{border-width:1px}';
		return tinyMCE;
	}
}