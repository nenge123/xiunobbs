var T = Nenge,I=T.I,F=T.F;
(function(){
    Object.assign(T.action,{
        mask(){
            var mask = T.$ct('div','<label class="mask-label" style="height: 20px;"><b class="mask-title"></b>:<progress class="mask-progress" max="100" value="20"></progress></label>','mask-content');
            document.body.appendChild(mask);
            return [mask,T.$('.mask-progress',mask),T.$('.mask-title',mask)];

        }
    });
    T.docload(async()=>{
        //await Nenge.addJS(Nenge.JSpath+'zip/webp_gif/webp_encoder.js');
        //window.encoder = new (await WebpEncoderWasm()).WebpEncoder();
        window.m = await Nenge.FetchItem({url:'test.gif',type:'blob'});
        document.body.appendChild(T.$ct('img','','',{src:F.URL(m,'gif')}));
        console.log(await T.gif2webp(m,{
    
            quality: 75,
            method: 4,
            lossless: !0,
            exact: !0,
          }));
    });
    Object.assign(T,{
        async image2webp(blob,quality){
            quality = quality||1;
            if(!I.blob(blob))blob = new Blob([blob.buffer||blob]);
            let img = await createImageBitmap(blob),
            opt = {alpha:!0,willReadFrequently:!0},
            canvas;
            return I.Async(async re=>{
                let callback = buf=>{
                    if(blob.name) return re(new File([buf],blob.name.replace(/\.\w+$/ig,'.webp'),{type:F.getMime('webp')}));
                    re(buf);
                }
                if(typeof OffscreenCanvas !='undefined'){
                    canvas = new OffscreenCanvas(img.width,img.height);
                    canvas.getContext("2d",opt).drawImage(img, 0, 0);
                    callback(await canvas.convertToBlob({type:F.getMime('webp'),quality}));
                }else{
                    canvas = T.$ct('canvas','','',{width:img.width,height:img.height});
                    canvas.getContext("2d",opt).drawImage(img, 0, 0);
                    canvas.toBlob(callback,F.getMime('webp'),quality);
                }
                img.close();
            });

        },
        /**
         * 
         * @param {File} gif  文件对象
         * @param {Object} frameOptions 参数
         * @returns {File} webp文件对象
         */
        async gif2webp(gif,frameOptions){
            //frameOptions = {quality: 90,method: 4,lossless: !0,exact: !0,}     
            let mask = T.progress_mask();
            if(!T.getWorker){
                T.get_Worker_gif2webp = new Promise(async re=>{
                    let files = await T.FetchItem({
                        url:T.JSpath+'zip/webp_encoder.zip',
                        unpack:!0,
                        version:4,
                        store:T.LibStore,
                        progress(a,b,c){
                            mask[2].innerHTML = 'webp_encoder.zip:';
                            if(b)mask[1].value = parseInt(b);
                        }
                    });
                    let worker = new Worker(F.URL(files['webp_encoder.min.js'],'js'));
                    //let worker = new Worker(T.JSpath+'zip/webp_gif/webp_encoder.js');
                    T.once(worker,'message',event=>{
                        var data = event.data;
                        if(data.type=='ok'){
                            re(worker);
                            files = null;
                        }
                    });
                    worker.postMessage({JSpath:T.JSpath,fileOptions:{},wasmBinary:files['webp_encoder.wasm']});
                });
            }
            let worker = await T.get_Worker_gif2webp;
            let time=T.time;
            mask[2].innerHTML = T.GL('convert');
            mask[1].value = 0;
            return new Promise(ok=>{
                let id = T.time+T.rand;
                let func = event=>{
                    let data = event.data;
                    if(data&&data.id==id){
                        if(typeof data.progress !='undefined'){
                            mask[1].value = parseInt(data.progress);
                            return;
                        }
                        T.un(worker,'message',func);
                        var size = data.data.length||data.data.size;
                        document.body.appendChild(T.$ct('p','time:'+((T.time-time)/1000).toFixed(2)+'S,size:'+(size/1024).toFixed(0)+'KB,压缩比:'+(size*100/m.size).toFixed(0)+'%'));
                        document.body.appendChild(T.$ct('img','','',{src:F.URL(data.data,'webp')}));
                        ok(data.data||null);
                        mask[0].remove();
                    }
                };
                T.on(worker,'message',func);
                worker.postMessage({gif,id,frameOptions});
            })
        },
        progress_mask(){
            var mask = T.$ct('div','<label class="mask-label" style="height:20px"><b>'+T.GL('progress')+':</b><progress class="mask-progress" max="100" value="0"></progress></label>','mask-content');
            document.body.appendChild(mask);
            return [mask,T.$('.mask-progress',mask),T.$('b',mask)];
        },
    });
}).call(Nenge);