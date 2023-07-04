var T = Nenge,
    I = T.I,
    F = T.F;
(function () {
    Object.assign(T.action, {
        mask() {
            var mask = T.$ct('div', '<label class="mask-label" style="height: 20px;"><b class="mask-title"></b>:<progress class="mask-progress" max="100" value="20"></progress></label>', 'mask-content');
            document.body.appendChild(mask);
            return [mask, T.$('.mask-progress', mask), T.$('.mask-title', mask)];

        }
    });
    T.docload(async () => {
        //await Nenge.addJS(Nenge.JSpath+'zip/webp_gif/webp_encoder.js');
        //window.encoder = new (await WebpEncoderWasm()).WebpEncoder();
        //window.m = await Nenge.FetchItem({url: 'test.gif?' + T.time,type: 'blob'});
        //window.z = await Nenge.FetchItem({url: 'test.zip?' + T.time,type: 'blob'});
        //document.body.appendChild(T.$ct('img', '', '', {src: F.URL(m, 'gif')}));
        //console.log(await T.gif2webp(m, {quality: 75,method: 4,lossless: !0,exact: !0,}));
        //console.log(T.show_gif((await T.im2webp(m,75)),T.time));
    });
    Object.assign(T, {
        async image2webp(blob, quality) {
            quality = quality&&quality/100 || 1;
            if (!I.blob(blob)) blob = new Blob([blob.buffer || blob]);
            var name = blob.name&&blob.name.replace(/\.\w+$/ig, '.webp')||'explame.webp';
            let img = await createImageBitmap(blob),
                opt = {
                    alpha: !0,
                    willReadFrequently: !0
                },
                canvas;
            return I.Async(async re => {
                let callback = buf => {
                    re(new File([buf],name, {type: F.getMime('webp')}));
                }
                if (typeof OffscreenCanvas != 'undefined') {
                    canvas = new OffscreenCanvas(img.width, img.height);
                    canvas.getContext("2d", opt).drawImage(img, 0, 0);
                    callback(await canvas.convertToBlob({
                        type: F.getMime('webp'),
                        quality
                    }));
                } else {
                    canvas = T.$ct('canvas', '', '', {
                        width: img.width,
                        height: img.height
                    });
                    canvas.getContext("2d", opt).drawImage(img, 0, 0);
                    canvas.toBlob(callback, F.getMime('webp'), quality);
                }
                img.close();
            });

        },
        get_webp_encoder_zip(mask) {
            return T.FetchItem({
                url: T.JSpath + 'zip/webp_encoder.zip',
                unpack: !0,
                version: 6,
                store: T.LibStore,
                progress(a, b, c) {
                    if (mask) {
                        mask[2].innerHTML = 'webp_encoder.zip:';
                        if (b) mask[1].value = parseInt(b);
                    }
                }
            })
        },
        show_gif(data, time) {
            if(data instanceof Array)data=data[0];
            time = time||T.time;
            var size = data.length || data.size;
            document.body.appendChild(T.$ct('p', 'time:' + ((T.time - time) / 1000).toFixed(2) + 'S,size:' + (size / 1024).toFixed(0) + 'KB,压缩比:' + (size * 100 / m.size).toFixed(0) + '%'));
            document.body.appendChild(T.$ct('img', '', '', {
                src: F.URL(data,F.getExt(data.name))
            }));
        },
        /**
         * 
         * @param {File} gif  文件对象
         * @param {Object} frameOptions 参数
         * @returns     {File} webp文件对象
         */
        async gif2webp(gif, frameOptions) {
            let time = T.time;
            //frameOptions = {quality: 90,method: 4,lossless: !0,exact: !0,}     
            let mask = T.progress_mask();
            if (!window.Worker) {
                var W = window;
                if (!T.get_encoder_gif2webp) {
                    let files = await T.get_webp_encoder_zip(mask);
                    //await T.addJS(F.URL(files['webp_encoder.min.js'],'js'));
                    await T.addJS(T.JSpath + 'zip/webp_gif/webp_encoder.js?'+T.time);
                    //await T.addJS(T.JSpath + 'zip/webp_gif/WebpEncoder.js?'+T.time);
                    W.gifJSpath = T.JSpath;
                    T.get_encoder_gif2webp = W.get_gif_webp_wasm(files['webp_encoder.wasm']);
                }
                mask[2].innerHTML = T.GL('convert');
                mask[1].value = 0;
                alert(await T.get_encoder_gif2webp);
                var data = await W.gif2webp({
                    gif,
                    frameOptions
                }, await T.get_encoder_gif2webp, p => {
                    mask[1].value = p;
                });
                mask[0].remove();
                return data;
            } else {
                if (!T.get_Worker_gif2webp) {
                    T.get_Worker_gif2webp = I.Async(async re => {
                        let files = await T.get_webp_encoder_zip(mask);
                        //let worker = new Worker(F.URL(files['webp_encoder.min.js'],'js'));
                        let worker = new Worker(T.JSpath + 'zip/webp_gif/webp_encoder.js?'+T.time);
                        T.once(worker, 'message', event => {
                            var data = event.data;
                            if (data.type == 'ok') {
                                re(worker);
                                files = null;
                            }
                        });
                        worker.postMessage({
                            JSpath: T.JSpath,
                            fileOptions: {},
                            wasmBinary: files['webp_encoder.wasm']
                        });
                    });
                }
                let worker = await T.get_Worker_gif2webp;
                mask[2].innerHTML = T.GL('convert');
                mask[1].value = 0;
                return I.Async(ok => {
                    let id = T.time + T.rand;
                    let func = event => {
                        let data = event.data;
                        if (data && data.id == id) {
                            if (typeof data.progress != 'undefined') {
                                mask[1].value = parseInt(data.progress);
                                return;
                            }
                            T.un(worker, 'message', func);
                            ok(data.data || null);
                            mask[0].remove();
                        }
                    };
                    T.on(worker, 'message', func);
                    worker.postMessage({
                        gif,
                        id,
                        frameOptions
                    });
                })

            }
        },
        im2webp_files(mask) {
            return T.FetchItem({
                url: T.JSpath + 'zip/wasm-im.zip',
                unpack: !0,
                version: 8,
                store: T.LibStore,
                progress(a, b, c) {
                    if (mask) {
                        mask[2].innerHTML = 'wasm-im.zip:';
                        if (b) mask[1].value = parseInt(b);
                    }
                }
            })
        },
        async im2webp(gif, quality) {
            let mask = T.progress_mask();
            if (!window.Worker) {
                var W = window;
                if (!T.im2webp_files_module) {
                    let files = await T.im2webp_files(mask);
                    //await T.addJS(F.URL(files['wasm-im.min.js'],'js'));
                    await T.addJS(T.JSpath + 'zip/wasm-im/wasm-im.js?'+T.time);
                    W.imJSpath = T.JSpath;
                    T.im2webp_files_module = W.get_wasm_im_module(files['wasm-im.wasm']);
                }
                mask[2].innerHTML = T.GL('convert');
                mask[1].value = 0;
                var data = await W.im2webp({gif,quality,mask:mask[1],ext:await F.CheckExt(gif)}, await T.im2webp_files_module);
                mask[0].remove();
                return data;
            } else {
                if (!T.im2webp_files_worker) {
                    T.im2webp_files_worker = I.Async(async re => {
                        let files = await T.im2webp_files(mask);
                        let worker = new Worker(F.URL(files['wasm-im.min.js'],'js'));
                        //let worker = new Worker(T.JSpath + 'zip/wasm-im/wasm-im.js?'+T.time);
                        T.once(worker, 'message', event => {
                            var data = event.data;
                            if (data.type == 'ok') {
                                re(worker);
                                files = null;
                            }
                        });
                        worker.postMessage({
                            JSpath: T.JSpath,
                            wasmBinary: files['wasm-im.wasm']
                        });
                    });
                }
                let worker = await T.im2webp_files_worker;
                mask[2].innerHTML = T.GL('convert');
                mask[1].value = 0;
                return I.Async(async ok => {
                    let id = T.time + T.rand;
                    let func = event => {
                        let data = event.data;
                        if (data && data.id == id) {
                            if (typeof data.progress != 'undefined') {
                                mask[1].value = parseInt(data.progress);
                                return;
                            }
                            T.un(worker, 'message', func);
                            ok(data.data || null);
                            mask[0].remove();
                        }
                    };
                    T.on(worker, 'message', func);
                    worker.postMessage({
                        gif,
                        id,
                        quality,
                        ext:await F.CheckExt(gif)
                    });
                })

            }
        },
        encode_webp_files(mask) {
            return T.FetchItem({
                url: T.JSpath + 'zip/webp_enc.zip',
                unpack: !0,
                version: 1,
                store: T.LibStore,
                progress(a, b, c) {
                    if (mask) {
                        mask[2].innerHTML = 'webp_enc.zip:';
                        if (b) mask[1].value = parseInt(b);
                    }
                }
            })
        },
        async encode_webp(blob, quality) {
            quality = quality || 100;
            if (window.OffscreenCanvas&&window.ImageDecoder) {
                return await T.img2bit(blob,quality);
            } else {
                let mask = T.progress_mask();
                if (!T.get_encode_webp_worker) {
                    T.get_encode_webp_worker = I.Async(async re => {
                        let files = await T.encode_webp_files(mask);
                        let worker = new Worker(F.URL(files['webp_enc.min.js'],'js'));
                        T.once(worker, 'message', event =>event.data.type == 'ok'&&re(worker));
                        worker.postMessage({JSpath: T.JSpath,wasmBinary: files['webp_enc.wasm']});
                        files=null;
                    });
                }
                let worker = await T.get_encode_webp_worker;
                mask[2].innerHTML = T.GL('convert');
                mask[1].value = 0;
                return I.Async(async ok => {
                    let id = T.time + T.rand;
                    let func = event => {
                        let data = event.data;
                        if (data && data.id == id) {
                            if (typeof data.progress != 'undefined') {
                                mask[1].value = parseInt(data.progress);
                                return;
                            }
                            T.un(worker, 'message', func);
                            ok(data.data || []);
                            mask[0].remove();
                        }
                    };
                    T.on(worker, 'message', func);
                    worker.postMessage({
                        imagedata:await T.img2bit(blob),
                        name:blob.name,
                        id,
                        config:{quality},
                    });
                })
            }
        },
        async img2bit(blob,quality){
            var bit = await createImageBitmap(blob);
            var _canvas = T.$ce('canvas');
            var width = bit.width;
            var height = bit.height;
            _canvas.setAttribute("width", width);
            _canvas.setAttribute("height",height);
            var ctx = _canvas.getContext("2d",{
                alpha: !0,
                willReadFrequently: !0
            });
            ctx.drawImage(bit, 0, 0);
            if(typeof quality !='undefined') return new Promise(r=>{
                _canvas.toBlob(b=>{
                    r([new File([b],blob.name.replace(/\.\w+$/,'.webp'),{type:F.getMime('webp')}),width,height]);
                    _canvas.remove();
                    bit.close();
                },F.getMime('webp'),quality/100)
            });
            var buf = ctx.getImageData(0, 0,width,height);
            bit.close();
            _canvas.remove();
            return buf;
        },
        progress_mask() {
            var mask = T.$ct('div', '<label class="mask-label" style="height:20px"><b>' + T.GL('progress') + ':</b><progress class="mask-progress" max="100" value="0"></progress></label>', 'mask-content');
            document.body.appendChild(mask);
            return [mask, T.$('.mask-progress', mask), T.$('b', mask)];
        },
    });
}).call(Nenge);