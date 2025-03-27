self.addEventListener('install',
	function (event) {
		console.log(event.type);
		return self.skipWaiting();
	}
);
self.addEventListener('activate',
	function (event) {
		event.target.clients.matchAll().then(clients => Array.from(clients, client => client.postMessage({ type: 'message', message: 'serviceWorker已经注册' })));
		return self.skipWaiting();
	}
);
const staticFileEXT = ['js', 'css', 'woff', 'woff2', 'ttf', 'eot', 'svg', 'wasm'];
self.addEventListener('fetch', function (event) {
	;
	const request = event.request;
	const url = new URL(request.url);
	const ext = url.pathname.split('.').pop()
	if (url.origin === location.origin) {
		//本地文件
		return false;
	}
	//远程文件
	if (staticFileEXT.includes(ext)) {
		switch (true) {
			case request.url.includes('://static'):
			case request.url.includes('://cdn'):
			case request.url.includes('cdn.com'):
			case request.url.includes('static.com'):
			case request.url.includes('cdn.net'):
			case request.url.includes('static.net'):
			case request.url.includes('cdnjs'):
				return event.respondWith(getResponse(request, 'cdn-files'));
				break;
		}
	}
	//缓存的CDN名单
	switch (url.hostname) {
		case "registry.npmmirror.com":
		case "unpkg.com":
		case 'lishijieacg.co':
		case 'www.ikdmjx.com':
		case 'cdn.staticfile.net':
		case 'www.staticfile.org':
		case 'cdn.bootcdn.net':
		case 'cdnjs.cloudflare.com':
		case 's4.zstatic.net':
		case 'lf6-unpkg.zstaticcdn.com':
		case 's4.zstatic.com':
			return event.respondWith(getResponse(request, 'cdn-files'));
			break;
	}
	return false;
});
const EventSourceList = new Map;

self.addEventListener('message', function (event) {
	const data = event.data;
	const source = event.source;
	if (data && data.constructor instanceof Object) {
		switch (data.type) {
			case 'update':
				registration.update();
				source.postMessage({
					form: 'serviceWorker',
					action: 'message',
					type: 'update',
					status: event.type,
					id:data.id
				});
				break;
			case 'EventSource':
				//预留后台消息接口
				if (data.action == 'open' && !EventSourceList.has(data.name)) {
					createEventSource(data.url,data.name,data.all?null:source,data.id);
				} else if (EventSourceList.has(data.name)) {
					EventSourceList.get(data.name).close();
					EventSourceList.delete(data.name);
				}
				break;
		}
	}
});
/**
 * 对所有已加载客户端发送消息
 */
function postAllMessage(message){
	self.clients.matchAll().then(list=>list.forEach(win=>win.postMessage(message)));
}

/**
 * EventSource 一种实时输出的接口(IIS不太友好) IIS什么设置PHP outputbuffer都是假的 
 * IIS:网站:配置编辑器->切换:system.webServer/handlers->编辑项->找到执行的PHP->responseBufferLimit->设置0,缺点是网站就彻底没了程序缓冲(content-length)
 * apache默认PHP配置就可以实现无需什么额外设置
 * set_time_limit(0); #脚本没超时限制
 * ignore_user_abort(true); #不好说
 * while (ob_get_level() > 0): @ob_end_clean(); endwhile; #关闭所有缓冲,IIS实际上有一个顶级缓存fastcgi关不掉,参考上面关掉
 * header("X-Accel-Buffering: no");
 * header("Content-Type: text/event-stream"); #非常重要
 * echo str_pad(' ', 1050); #兼容部分谷歌浏览
 * flush(); #兼容,一般可忽略
 * echo 'event:side' . PHP_EOL; #相当于响应side事件
 * echo 'id:' . $index . PHP_EOL;
 * echo 'data:{"progress":"0%"}' . PHP_EOL; #相当于事件里的event.data
 * echo 'retry: 10000' . PHP_EOL;
 * echo PHP_EOL . PHP_EOL; #重点 每条消息末端必须用两个\r\n隔开
 * flush(); #兼容,一般可忽略
 * ...循环
 * #脚本结束前必须相应一个close 告诉监听关闭.否则当前脚本结束,客户端会重新发起
 * #当然对于 短信提醒,那就很好用.但是脚本输出前最好用 sleep(1) 延时,否则访问结束就会离开重新发起
 * #如 输出header头;sleep(1); 查询消息并输出.
 */
function createEventSource(url, name, source,id) {
	const link = new EventSource(url);
	['open', 'message', 'progress','side'].forEach(v =>
		link.addEventListener(v, function (event) {
			const message = {
				form: 'serviceWorker',
				action: 'message',
				type: 'EventSource',
				status: event.type,
				data: event.data,
				id
			};
			source&&source.postMessage(message) || postAllMessage(message);
		}));
	['open', 'error'].forEach(v => link.addEventListener(v, function (event) {
		const message = {
			form: 'serviceWorker',
			action: 'message',
			type: 'EventSource',
			status: event.type,
			data: event.data,
			id
		};
		source&&source.postMessage(message) || postAllMessage(message);
		link.close();
		EventSourceList.delete(data.name);
	}));
	EventSourceList.set(name,link);
}
async function getResponse(request, cachename, isorigin) {
	if (request.mode == 'no-cors') {
		cachename += '-include';
	}
	const cache = await caches.open(cachename);
	let response = await cache.match(request);
	if (!(response instanceof Response)) {
		if (isorigin) {
			response = await fetch(request, { headers: this.no_cache });
			if ((response instanceof Response) && response.status == 200) {
				cache.put(request, response.clone());
			}
		} else {
			response = await fetch(request);
			if (response instanceof Response) {
				const status = response.status;
				switch (true) {
					case status === 200:
					case status === 0 && response.type == 'cors':
					case status === 0 && response.type == 'opaque':
						cache.put(request, response.clone());
						break;
				}
			}
		}
	}
	return response || new Response(undefined, { status: 404, statusText: 'not found' });
}