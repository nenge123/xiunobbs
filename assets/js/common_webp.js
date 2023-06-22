!(function(){
    var T = this,I=T.I,F=T.F;
    Object.assign(T,{
        image2webp_conf:{
            quality: 80,
            target_size: 0,
            target_PSNR: 0,
            method: 4,
            sns_strength: 50,
            filter_strength: 60,
            filter_sharpness: 0,
            filter_type: 1,
            partitions: 0,
            segments: 4,
            pass: 1,
            show_compressed: 0,
            preprocessing: 0,
            autofilter: 0,
            partition_limit: 0,
            alpha_compression: 1,
            alpha_filtering: 1,
            alpha_quality: 100,
            lossless: 0,
            exact: 0,
            image_hint: 0,
            emulate_jpeg_size: 0,
            thread_level: 0,
            low_memory: 0,
            near_lossless: 100,
            use_delta_palette: 0,
            use_sharp_yuv: 0,
        },
        async image2webp(data,width,height,sw,sh,x,y){
            x = x||0;
            y = y||0;
            sw = sw||width;
            sh = sh||height;
            if(data.constructor!= Uint8ClampedArray){
                let _canvas = document.createElement("canvas");
                if(data.constructor != ImageBitmap){
                    if(!I.blob(data))data = new Blob([data]);
                    data = await createImageBitmap(data,x,y,sw,sh);
                }
                _canvas.setAttribute("width", data.width);
                _canvas.setAttribute("height", data.height);
                width = data.width;
                height = data.height;
                _canvas.getContext("2d").drawImage(data, 0, 0);
                data = _canvas.getContext("2d").getImageData(0,0,data.width,data.height).data;
                _canvas.remove();
            }
            var worker = await T.image2webp_load();
            return new Promise(ok=>{
                var id = T.time+T.rand;
                let func = event=>{
                    let data = event.data;
                    if(data&&data.id==id){
                        T.un(worker,'message',func);
                        ok(data.data||null);
                    }
                };
                T.on(worker,'message',func);
                worker.postMessage({buf:data,width,height,id})
            });
        },
        async image2webp_load(){
            if(T.image2webp_Worker)return T.image2webp_Worker;
            T.image2webp_Worker =  new Promise(async ok=>{
                T.image2webp_Ready = !0;
                let mask = T.image2webp_mask();
                let files = await T.FetchItem({
                    url:T.JSpath+'zip/webp_enc.zip?'+T.time,
                    unpack:!0,
                    store:'libjs',
                    version:1,
                    progress(a,b,c,d){
                        mask[2].innerHTML = F.getname(a);
                        if(b)mask[1].value = parseInt(b);
                    }
                });
                var worker = new Worker(F.URL(files['webp_enc.js'],'js'));
                T.once(worker,'message',event=>{
                    if(event.data&&event.data.type=='ok'){
                        mask[0].remove();
                        ok(worker);
                        T.image2webp_Worker = worker;
                    }
                });
                worker.postMessage({wasmBinary:files['webp_enc.wasm']})
            });
            return T.image2webp_Worker;
        },
        image2webp_mask(){
            var mask = T.$ct('div','<label class="mask-label"><p>image2webp</p><b>'+T.GL('progress')+':</b><progress class="mask-progress" max="100" value="0"></progress></label>','mask-content');
            document.body.appendChild(mask);
            return [mask,T.$('progress',mask),T.$('b',mask)];
        },
    });
    T.image2webp_load();
}).call(Nenge);