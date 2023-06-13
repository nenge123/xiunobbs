var CACHE_NAME = 'XIUNOBBS';//if you have any version update change here
var CACHE_PATH = serviceWorker.scriptURL.split('/').slice(0, -1).join('/') + '/';
//定义一个特点缓存版本
//var VERSION = caches.match('my_cache_data_version', { cacheName: CACHE_NAME });
Object.entries(
    {
        install(event) {
            console.log('serviceWorker install');
            return self.skipWaiting();//跳过等待
        },
        activate(event) {
            console.log('serviceWorker activate');
            return self.skipWaiting();//跳过等待
        },
        fetch(event) {
            //拦截请求 event.request 一个请求对象
            return event.respondWith(new Promise(async resolve => {
                var url = event.request.url.replace(CACHE_PATH, ''), cacheTime;
                const cache = await caches.open(CACHE_NAME);
                var response = await cache.match(event.request);
                if (navigator.onLine) {
                    //联网状态
                    if (!response) {
                        response = await fetch(event.request);
                        if(response&&response.status==200){
                            if (url.match(/(static\/)[^\s]+\.\w+$/)) {
                                //特定条件缓存
                                cache.put(event.request, response.clone());
                            } else {
                                //console.log(event.request.url);
                            }
                        }
                    }
                    /* else if (url.match(/cache\/(data|css)\/\w+\.(js|css)\?\d+$/)) {
                        //特地缓存自动更新管理规则 xx?time
                        var version = url.split('?')[1];
                        var responseVer = await VERSION;
                        if (!responseVer || responseVer.headers.get('ver') != version) {
                            var list = await cache.matchAll(url.split('?')[0], { ignoreSearch: true });
                            if (list) list = list.filter(v => {
                                var bool = v.url == url;
                                if (!bool) {
                                    cache.delete(v);
                                    console.log('remove ' + v.url);
                                }
                                return bool;
                            });
                            if (!list.length) {
                                response = await fetch(event.request);
                                cache.put(event.request, response.clone());
                            }
                            cache.put('my_cache_data_version', new Response('', { headers: { ver: version } }));
                        }
                    }
                    */
                }
                resolve(response);

            }));
        },
        message(event) {
            console.log(event.data);
        }
    }
).forEach(
    entry => {
        self.addEventListener(entry[0], entry[1]);
    }
);