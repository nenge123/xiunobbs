!(function(){
    var T = this,I=T.I,F=T.F;
    Object.assign(T,{
        async gif2webp(buf,width,height,quality,inputName,outputName){
            var resize;
            quality = quality||75;
            if(width&&height){
                resize = {width,height};
                quality = 100;
            }
            return new Promise(async ok=>{
                var worker = await T.gif2webp_load();
                var id = T.time+T.rand;
                let func = event=>{
                    let data = event.data;
                    if(data&&data.id==id){
                        T.un(worker,'message',func);
                        ok(data.data||null);
                    }
                };
                T.on(worker,'message',func);
                worker.postMessage({buf,resize,id,quality,inputName,outputName})
            });
        },
        async gif2webp_load(){
            if(T.gif2webp_Worker)return T.gif2webp_Worker;
            T.gif2webp_Worker =  new Promise(async ok=>{
                T.gif2webp_Ready = !0;
                let mask = T.gif2webp_mask();
                let files = await T.FetchItem({
                    url:T.JSpath+'zip/wasm-im.zip?'+T.time,
                    unpack:!0,
                    store:'libjs',
                    version:1,
                    progress(a,b,c,d){
                        mask[2].innerHTML = F.getname(a);
                        if(b)mask[1].value = parseInt(b);
                    }
                });
                var worker = new Worker(F.URL(files['wasm-im.js'],'js'));
                //var worker = new Worker(T.JSpath+'zip/wasm-im/wasm-im.js');
                T.once(worker,'message',event=>{
                    if(event.data&&event.data.type=='ok'){
                        mask[0].remove();
                        ok(worker);
                        T.gif2webp_Worker = worker;
                    }
                });
                worker.postMessage({wasmBinary:files['wasm-im.wasm']})
            });
            return T.gif2webp_Worker;
        },
        gif2webp_mask(){
            var mask = T.$ct('div','<label class="mask-label"><p>gif2webp</p><b>'+T.GL('progress')+':</b><progress class="mask-progress" max="100" value="0"></progress></label>','mask-content');
            document.body.appendChild(mask);
            return [mask,T.$('progress',mask),T.$('b',mask)];
        },
    })
}).call(Nenge);