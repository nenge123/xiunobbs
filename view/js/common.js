const url = import.meta.url;
const webroot = url.split('/').slice(0,-3).join('/')+'/';
console.log(webroot);
const X = new class extends EventTarget{
	methods = new Map;
	constructor(){
		super();
		const sw = navigator.serviceWorker;
		if(sw){
			sw.addEventListener('message',e=>this.callMethod('sw',e.data,e.source,e.type));
			this.sw = sw.register(webroot+'sw.js').then(SW=>SW.active);
			this.methods.set('sw',function(event){console.log(event);});
		}	
	}
	callMethod(method,...arg){
		const data = this.methods.get(method);
		if(data instanceof Function) return data.call(this,...arg);
		return data;
	}
	async postMessage(message){
		if(this.sw){
			const sw = await this.sw;
			sw.postMessage(message);
		}
	}
}
Object.defineProperty(self,'X',{
	get:()=>X
});
export default X;