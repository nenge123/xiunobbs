Object.assign(self,
    {
        CACHE_NAME: 'XIUNOBBS',
        CACHE_PATH: self.registration.scope,
        CDN: ['cdn.staticfile.org', 's1.hdslb.com'],
        CACHE_DB: null,
        getResponse(url, action) {
            return new Promise(back => {
                var func = e => {
                    console.log(e);
                    var data = e.data;
                    if (data && data.url == url && data.action == action) {
                        removeEventListener('message', func);
                        return back(data.response ? true : null);
                    }
                };
                addEventListener('message', func);
                postMessage({ url, action });
            });
        }, getHeaders(headers) {
            var objs = {};
            headers.forEach((a, b) => objs[b] = a);
            return objs;
        }, checkCDN(url) {
            for (var i = 0; i < CDN.length; i++) {
                if (url.search(CDN[i]) !== -1) return !0;
            }
            return !1;
        }, postMessage(str) {
            self.clients.matchAll().then(WindowClients => WindowClients.forEach(Clients => {
                if (Clients.visibilityState == 'visible') {
                    Clients.postMessage(str);
                }
            }))
        }
    });
Object.entries({
    install(event) {
        console.log('serviceWorker install');
        return self.skipWaiting(); //跳过等待
    },
    activate(event) {
        console.log('serviceWorker activate');
        return self.skipWaiting(); //跳过等待
    },
    fetch(event) {
        if (event.request.method == 'GET') {
            var url = event.request.url,
                pathfile = url.replace(CACHE_PATH, '');
            if (navigator.onLine && url.search(location.hostname) != -1) {
                //本地网络
                if (/^(?!assets|cache|plugin).*/.test(pathfile)) {
                    //跳过路由不缓存
                    return;
                }
                if (/^assets\/js\/common\w*\.js/.test(pathfile)) {
                    //核心文件不缓存
                    return;
                }
            }
            //拦截请求 event.request 一个请求对象
            return event.respondWith(new Promise(async resolve => {
                if (location.protocol == 'http:') url = url.replace('https:', location.protocol);
                if (!CACHE_DB) CACHE_DB = await caches.open(CACHE_NAME);
                var headers, response = await CACHE_DB.match(event.request);
                if (navigator.onLine) {
                    //联网状态
                    if (!response) {
                        response = await fetch(event.request).catch(async e => {

                            if (url.search(location.hostname) !== -1) {
                                //分析本地虚假地址 进行虚假worker缓存
                                action = null;
                                if (url.match(/tinymce\.min\.js$/)) action = 'tinymce_load'; //初始化编辑器缓存
                                if (action) {
                                    if (await getResponse(url, action)) {
                                        response = await CACHE_DB.match(event.request);
                                    }
                                }
                            }
                            resolve(response);
                        });
                        if (response) {
                            if (checkCDN(url) || (response.status == 200 && /(static\/)[^\s]+\.\w+$/.test(url))) {
                                CACHE_DB.put(event.request, response.clone());
                            }
                        }
                    }
                }
                resolve(response);
            }));
        }
    },
    message(event) {
        console.log(event.data);
    }
}).forEach(
    entry => self.addEventListener(entry[0], entry[1])
);