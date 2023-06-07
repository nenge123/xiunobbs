var CACHE_PREX = 'XIUNOBBS_';
var CACHE_NAME = CACHE_PREX + 'v1';//if you have any version update change here
var CACHE_PATH = serviceWorker.scriptURL.split('/').slice(0, -1).join('/') + '/';
var VERSION = caches.match('my_cache_data_version', { cacheName: CACHE_NAME });
Object.entries(
    {
        install(event) {
            //注册,如果本脚本发生改变 会重新注册
            console.log('serviceWorker install');
            return self.skipWaiting();//跳过等待
            event.waitUntil(
                caches.open(CACHE_NAME).then(
                    cache => cache.addAll(urlsToCache)
                ).then(() => {
                    console.log('Cache downloaded', caches)
                    self.skipWaiting()
                })
            );
        },
        activate(event) {
            //激活变化 初始化
            //清空特定数据const cache = await caches.open(CACHE_NAME);cache.delete(url);
            console.log('serviceWorker activate');
            event.waitUntil(
                caches.keys().then(function (cacheNames) {
                    return Promise.all(
                        cacheNames.map(function (cacheName) {
                            if (CACHE_NAME != cacheName && cacheName.includes(CACHE_PREX)) {
                                //移除特定旧缓存数据库
                                return caches.delete(cacheName);
                            }
                        })
                    );
                })
            );
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
                            if ('custom_function' in self) {
                                self['custom_function'](response, cache, event.request);
                            } else if (url.match(/(static\/)[^\s]+\.\w+$/)) {
                                cache.put(event.request, response.clone());
                            } else {
                                //console.log(event.request.url);
                            }
                        }
                    } else if (url.match(/cache\/(data|css)\/\w+\.(js|css)\?\d+$/)) {
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
                }
                resolve(response);

            }));
        },
        message(event) {
            console.log(event.data);
            //event.source.postMessage('I know');
        }
    }
).forEach(
    entry => {
        self.addEventListener(entry[0], entry[1]);
    }
);
//importScripts(CACHE_PATH+'cache/data/sw.js');