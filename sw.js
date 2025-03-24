self.addEventListener('install',
	function (event) {
		console.log(event.type);
		return self.skipWaiting();
	}
);
self.addEventListener('activate',
	function (event) {
		event.target.clients.matchAll().then(clients => Array.from(clients, client => client.postMessage({ type: 'message', message: 'serviceWorker已经注册'})));
		self.registration.update();
		return self.skipWaiting();
	}
);
self.addEventListener('fetch', function (event) {
	;
	const request = event.request;
	const url = new URL(request.url);
	const ext = url.pathname.split('.').pop()
	if (url.origin === location.origin) {
		//本地文件
	} else {
		//远程文件
		if (['js', 'css', 'woff', 'woff2', 'ttf', 'eot', 'svg'].includes(ext)) {
			switch (true) {
				case request.url.includes('://static.'):
				case request.url.includes('://cdn.'):
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
				return event.respondWith(getResponse(request, 'cdn-files'));
				break;
		}
	}
	return false;
});
const EventSourceList = new Map;
self.addEventListener('message', function (event) {
	const data = event.data;
	if (data && data.constructor instanceof Object) {
		switch (data.type) {
			case 'update':
				registration.update();
				break;
			case 'EventSource':
				if (data.action == 'open' && !EventSourceList.has(data.name)) {
					EventSourceList.set(data.name, new EventSource(data.url));
				} else if (EventSourceList.has(data.name)) {
					EventSourceList.get(data.name).close();
					EventSourceList.delete(data.name);
				}
				break;
		}
	}
});
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