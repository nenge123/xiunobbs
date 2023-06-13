var T = Nenge,I=T.I,F=T.F;
!(function(){
    Object.assign(this,
        {
            async load_tinymce(){
                if('serviceWorker' in navigator){
                    //worker is open
                    let mycache = await caches.open('XIUNOBBS');
                    let path = T.JSpath+'tinymce/tinymce.min.js';//此地址虚假,不存在文件
                    let response = await mycache.match(path);
                    if(!response){
                        //下载并且用 (gildas-lormeau/zip.js)解压
                        let files = await T.FetchItem({url:T.JSpath+'zip/tinymce.zip',unpack:true});
                        await I.Async(I.toArr(files).map(async entry=>{
                            let re = new Response(
                                //new File()
                                I.R(14,[entry[1]],F.getname(entry[0]),{type:F.getMime(entry[0])}),
                                {headers:{status:200,'Content-Length':entry[1].byteLength}}
                            );
                            //写入至serverWorker缓存中
                            await mycache.put(T.JSpath+entry[0],re);
                        }));
                    }
                    T.addJS(path);
                }else{
                    T.addJS('https://cdn.staticfile.org/tinymce/6.5.0/tinymce.min.js');
                }
            }
        }
    );
}).call(T);