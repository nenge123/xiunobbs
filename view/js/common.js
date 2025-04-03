import methods from './methods.js';
import ajaxs from './ajax.js';
const url = import.meta.url;
const jsroot = url.split('/').slice(0, -1).join('/') + '/';
Object.assign(EventTarget.prototype, {
	/**
	 * 绑定事件
	 * @param {*} evt 
	 * @param {*} fun 
	 * @param {*} opt 
	 * @returns 
	 */
	on(evt, fun, opt) {
		return this.addEventListener(evt, fun, opt), this;
	},
	/**
	 * 解绑事件
	 * @param {*} evt 
	 * @param {*} fun 
	 * @param {*} opt 
	 * @returns 
	 */
	un(evt, fun, opt) {
		return this.removeEventListener(evt, fun, opt), this;
	},
	/**
	 * 绑定一次事件
	 * @param {*} evt 
	 * @param {*} fun 
	 * @param {*} opt 
	 * @returns 
	 */
	once(evt, fun) {
		return this.on(evt, fun, { passive: !1, once: !0 }), this;
	},
	/**
	 * 触发自定义事件
	 * @param {*} evt 
	 * @param {*} detail 
	 */
	toEvent(evt, detail) {
		return this.dispatchEvent(typeof evt == 'string' ? new CustomEvent(evt, { detail }) : evt), this;
	}
});
class xiuno extends EventTarget {
	jsroot = jsroot;
	cdn_unpkg_path = 'https://lf6-unpkg.zstaticcdn.com/';
	webp_wasm_path = this.cdn_unpkg_path + 'wasm-webp@0.0.2/dist/esm/';
	zip_js = this.cdn_unpkg_path + '@zip.js/zip.js@2.7.53/dist/zip.min.js';
	form_js = this.cdn_unpkg_path + 'formdata-polyfill@4.0.10/formdata.min.js';
	methods = new Map;
	ajaxs = new Map;
	constructor() {
		super();
		Object.entries(methods).forEach(v => this.methods.set(v[0], v[1]));
		Object.entries(ajaxs).forEach(v => this.ajaxs.set(v[0], v[1]));
		this.once('ready', this.ready);
	}
	callMethod(method, ...arg) {
		const data = this.methods.get(method);
		if (data instanceof Function) return data.call(this, ...arg);
		return data;
	}
	callAjax(method, ...arg) {
		const data = this.ajaxs.get(method);
		if (data instanceof Function) return data.call(this, ...arg);
		return data;
	}
	async postMessage(message) {
		if (this.sw) {
			const sw = await this.sw;
			sw.postMessage(message);
		}
	}
	ready() {
		console.log('ready');
		this.vieworigin = (new URL(this.viewroot||'',location.href)).origin;
		const sw = navigator.serviceWorker;
		if (sw) {
			sw.addEventListener('message', e => this.callMethod('sw', e.data, e.source, e.type));
			if(sw.ready){
				this.sw = sw.ready.then(s=>s.active);
				sw.addEventListener('controllerchange',event=>this.sw = event.target.ready.then(s=>s.active));
			}else{
				this.sw = new Promise(back=>sw.register(this.webroot + 'sw.js').then(s=>back(s.active)));
			}
			this.methods.set('sw', function (event) { console.log(event); });
		}
		this.parse();
	}
	parse(dom) {
		let domId = null;
		if (!dom) {
			dom = document.body;
		} else {
			if (dom.getAttribute('id')) {
				domId = '#' + dom.getAttribute('id');
			}
		};
		Array.from(dom.querySelectorAll('[onmethods]'), elm => {
			elm.getAttribute('onmethods').split(',').forEach(v => this.callMethod(v, elm, domId));
			elm.removeAttribute('onmethods');
		});
		Array.from(dom.querySelectorAll('[onget]'), elm => this.callAjax('onajax', elm, 'onget', domId));
		Array.from(dom.querySelectorAll('[onpost]'), elm => this.callAjax('onajax', elm, 'onpost', domId));
	}
	isFrom(elm) {
		return elm instanceof HTMLFormElement;
	}
	isOBJ(o) {
		return o && o.constructor instanceof Object
	}
	isPOST(o){
		return o instanceof FormData;
	}
	isJQ(o){
		return o instanceof self.jQuery
	}
};
$.fn.extend({
	/**
	 * 禁用提交按钮
	 */
	disabled(bool) {
		if (X.isFrom(this[0])) return this.find('[type=submit]').disabled(bool), this;
		const status = this.prop('disabled');
		if (bool === false) {
			status && this.prop('disabled', false).removeClass('disabled').html(this.attr('default-text') || this.data('default-text') || this.text());
		} else if (!status) {
			this.prop('disabled', true).addClass('disabled').attr('default-text', this.text());
			let loading_text = this.attr('loading-text') || this.data('loading-text') || typeof bool == 'string' && bool;
			if (loading_text) this.html(loading_text);
		}
		return this;
	},
});
$.extend({
	reload(url,time){
		if(time&&time>0){
			return setTimeout(()=>url.indexOf('javascript')!==-1?(new Function(url))():location.href=url,time>500?time:time*1000);
		}
		location.href=url;
	}
});
const X = new xiuno();
Object.defineProperty(self, 'X', {
	get: () => X
});
export default self.X;