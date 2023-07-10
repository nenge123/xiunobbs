!(function () {
    var T = this,
        I = T.I,
        F = T.F;
    var lang = {
        'zh-CN': {
            'fonttext': '文本',
            'edit_loading': '编辑器加载中',
            'progress': '进度',
            'Submint': '发布',
            'Replay': '回帖',
            'attach': '附件',
            'attachfile': '上传附件',
            'attachimg': '上传图片',
            'attachunuser': '未使用附件',
            'uploading...': '数据上传中...',
            'request...': '数据下载中...',
            'image-html-text': '尺寸:{width}x{height},大小:{size}',
            'byteLength': '字节',
            'Compress': '压缩',
            'Quality': '质量',
            'size-too-big': '文件过大(超过512KB)!<br>请调整质量后压缩,每次压缩不可回档.<br>如果是GIF图片,请务必调整图像大小在480x320内,否则IOS手机端可能内存不足.导致崩溃,压缩过慢!<br>如需要上传高清图,请使用附件方式.',
            'not-a-image': '这不是有效图片文件,请重试!',
            'image-is-small': '图片已经非常符合要求,确定中止压缩吗?',
            'attach-tips': '非压缩文件会自动转换为ZIP压缩文件,并且你可以设置密码.附件支持在线预览内部图片等功能.',
            'set-filename': '设置文件名(可选)',
            'set-password': '设置密码(可选)'
        },
        'zh-TW': {
            'fonttext': '文本',
            'edit_loading': '编辑器載入中',
            'progress': '进程',
            'Submint': '发布',
            'Replay': '回帖'
        },
        'en': {
            'fonttext': 'text',
            'edit_loading': 'edit loading...'
        }
    };
    if (lang[T.language]) Object.assign(T.lang, lang[T.language]);
    else Object.assign(T.lang, lang['en']);
    Object.assign(this, {
        max_upload_size: 1887436,
        max_img_upload_size: 524288,
        tinymce_conf: {
            promotion: !1, //隐藏升级按钮
            branding: !1, //隐藏官网链接
            plugins: [
                "accordion", "advlist", "anchor", "autolink", "autoresize", "autosave", "charmap", "code", "directionality", "emoticons", "fullscreen", "image", "insertdatetime", "link", "lists", "media", "nonbreaking", "pagebreak", "preview", "quickbars", "save", "searchreplace", "table", "visualblocks", "visualchars", "wordcount"
            ],
            suffix: '.min',
            //icons: 'thin',\
            base_url: location.protocol + '//cdn.staticfile.org/tinymce/6.5.0/',
            skin: (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'oxide-dark' : 'oxide'),
            content_css: (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'default'),
            //skin_url:'https://cdn.staticfile.org/tinymce/6.5.0/skins/ui/oxide',
            language: 'zh-Hans',
            language_url: T.JSpath + 'tinymce/langs/zh-Hans.js',
            toolbar: !1,
            toolbar_location: 'toolbar_sticky',
            selector: '.fastpost-textarea',
            menubar: 'file attachmenu insert edit format table tools view fonttext',
            min_height: 400,
            mobile: {},
            menu: {
                'file': {
                    title: T.GL('Replay'),
                    items: 'submintbtn | preview | export print'
                },
                'fonttext': {
                    title: T.GL('fonttext'),
                    items: 'bold superscript subscript forecolor backcolor removeformat'
                },
                'attachmenu': {
                    title: T.GL('attach'),
                    items: 'attachfile | attachimg | attachunuser'
                }
            },
            setup: (editor) => {
                editor.ui.registry.addMenuItem('submintbtn', {
                    text: T.GL('Submint'),
                    icon: 'browse',
                    onAction: (e) => T.tinymce_submit(editor, e)
                });
                editor.ui.registry.addMenuItem('attachfile', {
                    text: T.GL('attachfile'),
                    icon: 'browse',
                    onAction: (e) => T.tinymce_open_uploads(editor, e)
                });
                editor.ui.registry.addMenuItem('attachimg', {
                    text: T.GL('attachimg'),
                    icon: 'browse',
                    onAction: (e) => T.tinymce_open_uploadimg(editor, e)
                });
                editor.ui.registry.addMenuItem('attachunuser', {
                    text: T.GL('attachunuser'),
                    icon: 'browse',
                    onAction: (e) => T.tinymce_getattach(editor, e)
                });
                T.tinymce_request = (post, dialogApi, text) => T.CF('attach_ajax', post, (type, ...arg) => {
                    switch (type) {
                        case 'attachs':
                        case 'images':
                            console.log(arg);
                            editor.insertContent(arg[0]);
                            T.$('[name="attachid"]').value += arg[1] + ',';
                            break;
                        case 'msg':
                            editor.windowManager.alert(arg[0]);
                            break;
                        case 'close':
                            dialogApi.close();
                            break;
                    }
                }, text);
            }
        },
        async tinymce_files_add() {
            if (T.serviceActive) {
                let path = T.JSpath + 'tinymce/tinymce.min.js'; //此地址虚假,不存在文件
                this.tinymce_conf.plugins.push("help");
                this.tinymce_conf.menubar += " help";
                this.tinymce_conf.base_url = T.JSpath + 'tinymce';
                await T.addJS(path);
            } else {
                await T.addJS(location.protocol + '//cdn.staticfile.org/tinymce/6.5.0/tinymce.min.js');
            }
            this.tinymce_conf.mobile.menubar = this.tinymce_conf.menubar;
        },
        tinymce_mask() {
            var mask = T.$ct('div', '<label class="mask-label"><p>TinyMce' + T.GL('edit_loading') + '</p><b>' + T.GL('progress') + ':</b><progress class="mask-progress" max="100" value="20"></progress></label>', 'mask-content');
            document.body.appendChild(mask);
            return [mask, T.$('progress', mask), T.$('b', mask)];
        },
        tinymce_submit(editor) {
            if (T.$('.fastpost-form')) {
                let elm = T.$('.fastpost-form');
                let post = I.post(elm);
                post.set('message', editor.getContent());
                post.set('doctype', 0);
                if (tinymce.activeEditor.plugins.fullscreen.isFullscreen()) tinymce.activeEditor.ui.registry.getAll().menuItems.fullscreen.onAction();
                T.ajax({
                    post,
                    url: elm.action,
                    type: 'text',
                    success(a, b, c) {
                        console.log(a, b, c);
                        window.kk = c;
                        tinymce.activeEditor.setContent('');
                    }
                });
            }
        },
        tinymce_obj() {
            return tinymce.activeEditor;
        },
        tinymce_wm() {
            return this.tinymce_obj().windowManager;
        },
        tinymce_alert(str) {
            return this.tinymce_wm().alert(str)
        },
        tinymce_confirm(msg, fn) {
            return this.tinymce_wm().confirm(msg, fn);
        },
        tinymce_open_uploads(editor, e) {
            editor.windowManager.open({
                title: 'Upload',
                body: {
                    type: 'panel',
                    items: [
                        {
                            type: 'input', // component type
                            name: 'password', // identifier
                            inputMode: 'text',
                            label: '密码', // text for the label
                            placeholder: T.GL('set-password'), // placeholder text for the input
                            enabled: !0, // disabled state
                        },
                        {
                            type: 'input', // component type
                            name: 'filename', // identifier
                            inputMode: 'text',
                            label: '文件名', // text for the label
                            placeholder: T.GL('set-filename'), // placeholder text for the input
                            enabled: !0, // disabled state
                        },
                        {
                            type: 'htmlpanel', // an HTML panel component
                            html: `<div class="attach-tips">${T.GL('attach-tips')}</div>`
                        }
                    ]
                },
                buttons: [{
                    type: 'cancel',
                    name: 'cancel',
                    text: 'Cancel',
                    enabled: !0,
                },
                {
                    type: 'submit',
                    name: 'submit',
                    text: 'Upload',
                    enabled: !0,
                }
                ],
                async onSubmit(dialogApi, details) {
                    const data = dialogApi.getData();
                    console.log(data);
                    T.CF('attach_expload', async files => {
                        var blob;
                        var basename = (data.filename || F.getKeyName(files[0].name));
                        var filetype = { type: F.getMime('zip') };
                        var filename = `${basename}.zip`;
                        var mask = T.progress_mask();
                        mask[2].innerHTML = filename;
                        var zip_progress = (loaded, total) => {
                            mask[1].value = (loaded * 100 / total).toFixed(0);
                        };
                        if (files.length > 1) {
                            blob = await T.CF('files2zip', files, zip_progress, data.password || undefined);
                        } else {
                            let mime = await F.CheckExt(files[0]);
                            if (['zip', 'rar', '7z'].includes(mime)) {
                                filetype = { type: F.getMime(mime) };
                                filename = `${basename}.${mime}`;
                                blob = files[0];
                            } else {
                                blob = await T.CF('files2zip', files,zip_progress, data.password || undefined);
                            }
                        }
                        if (blob && blob.size) {
                            var myFile = new File([blob], filename, filetype);
                            blob = null;
                            files=null;
                            if (myFile.size < 2097152) {
                                /**2MB */
                                mask[0].remove();
                                T.tinymce_request(I.post({ 'attchfile':myFile}), dialogApi, filename);
                            } else {
                                /**大文件上传 */
                                var filesize = myFile.size;
                                var filemd5 = await T.CF('md5file', myFile);
                                const splitsize = 1887436;
                                var postData = [];
                                for (var filepos = 0; filepos < filesize;) {
                                    var pathsize = filepos+splitsize;
                                    if(pathsize>filesize)pathsize = filesize;
                                    var xfile = new File([myFile.slice(filepos, pathsize)], filename, { type: 'application/x-path' });
                                    postData.push(I.post({
                                        attchfile: xfile,
                                        filesize,
                                        filepos,
                                        filemd5,
                                        pathsize
                                    }));
                                    xfile=null;
                                    filepos = pathsize;
                                }
                                var newpost;
                                var len = postData.length;
                                var lenpos = 1;
                                while(newpost = postData.shift()){
                                    var state = `(${lenpos}/${len})`;
                                    lenpos+=1;
                                    await T.ajax({
                                        url: location.href,
                                        post:newpost,
                                        postProgress(per, current, total) {
                                            mask[1].value = parseInt(per);
                                            mask[2].innerHTML = state+T.GL('uploading...');
                                        },
                                        progress(per, current, total) {
                                            mask[1].value = parseInt(per);
                                            mask[2].innerHTML = state+T.GL('request...');
                                        },
                                        success(text, headers) {
                                            console.log(text, headers);
                                            if (headers['content-type'] == 'application/json') {
                                                var result = JSON.parse(text);
                                                if(result.attachs){
                                                    editor.insertContent(result.attachs.map(v=>'[attach]' + v + '[/attach]').join(''));
                                                    T.$('[name="attachid"]').value += result.attachs.join(',') + ',';
                                                    mask[0].remove();
                                                    dialogApi.close();
                                                }

                                            }
                                        },
                                        error(){
                                            mask[0].remove();
                                            dialogApi.close();
                                        }
                                    });
                                    newpost = null;
                                }
                            }
                            myFile= null;
                        }
                        //T.download('aa.zip',blob);
                    }, '*', !0);
                }
            });
        },
        tinymce_open_uploadimg(editor) {
            var ImgFile, ImgType, uploadFile;
            T.CF('tinymce_attach_css');
            editor.windowManager.open({
                title: 'Uploading image',
                body: {
                    type: 'panel', // The root body type - a Panel or TabPanel
                    items: [{
                        type: 'imagepreview', // component type
                        name: 'preview', // identifier
                        height: '280px', // optional CSS height to constrain the image preview to
                    },
                    {
                        type: 'grid', // component type
                        columns: 2, // number of columns
                        style: 'align-items: center;',
                        items: [{
                            type: 'input', // component type
                            name: 'filesize', // identifier
                            inputMode: 'text',
                            label: T.GL('byteLength'), // text for the label
                            enabled: false, // disabled state
                            maximized: false // grow width to take as much space as possible
                        }, {
                            type: 'sizeinput', // component type
                            name: 'size', // identifier
                            label: 'Dimensions',
                            enabled: false // enabled state
                        }] // array of panel components
                    },
                    {
                        type: 'grid', // component type
                        columns: 2, // number of columns
                        name: 't2',
                        items: [{
                            type: 'slider', // component type
                            name: 'quality', // identifier
                            label: T.GL('Quality'),
                            min: 0, // minimum value
                            max: 100 // maximum value
                        }, {
                            type: 'button', // component type
                            text: T.GL('Compress'),
                            name: 'compress',
                            align: 'end',
                            enabled: true,
                            borderless: false
                        }]
                    }, {
                        type: 'input', // component type
                        name: 'title', // identifier
                        inputMode: 'text',
                        label: 'Image title', // text for the label
                        placeholder: 'example', // placeholder text for the input
                        enabled: false, // disabled state
                        maxlength: '120',
                    }
                    ]
                },
                buttons: [{
                    type: 'custom',
                    name: 'expImg',
                    text: "Browse for an image",
                },
                {
                    type: 'cancel',
                    name: 'cancel',
                    text: 'Cancel',
                    enabled: !0,
                },
                {
                    type: 'submit',
                    name: 'submit',
                    text: 'Upload',
                    enabled: !1,
                }
                ],
                initialData: {
                    quality: 75,
                },
                async onAction(dialogApi, details) {
                    const apidata = dialogApi.getData();
                    console.log(dialogApi, details, this, apidata);
                    if (details.name == 'expImg') {
                        T.CF('attach_expload', async files => {
                            ImgFile = files[0];
                            var Info = await T.getImageSize(ImgFile);
                            if (!Info[0]) editor.windowManager.alert(T.GL('not-a-image'));
                            ImgType = Info[2];
                            dialogApi.setData({
                                preview: {
                                    url: F.URL(ImgFile, ImgType)
                                },
                                filesize: (ImgFile.size / 1024).toFixed(0) + 'KB',
                                size: {
                                    width: Info[0] + 'px',
                                    height: Info[1] + 'px',
                                },
                                title: ImgFile.name
                            });
                            dialogApi.setEnabled('title', !0);
                            if (T.max_img_upload_size > ImgFile.size) {
                                dialogApi.setEnabled('size', !1);
                                dialogApi.setEnabled('submit', !0);
                            } else {
                                dialogApi.setEnabled('size', !0);
                                dialogApi.setEnabled('submit', !1);
                                if (ImgFile.size > 1024 * 1024 || ImgType == 'gif') {
                                    editor.windowManager.alert(T.GL('size-too-big'));
                                }
                            }
                        }, 'image/*');
                    } else if (details.name == 'compress' && ImgFile) {
                        if (ImgFile.size < 256 * 1024) {
                            if (await I.Async(re => {
                                tinymce.activeEditor.windowManager.confirm(T.GL('image-is-small'), state => re(state));

                            })) return;
                        }
                        let size = apidata.size;
                        let width = parseInt(size.width);
                        let height = parseInt(size.height);
                        var quality = parseInt(apidata.quality);
                        var imgData = await T.im2webp(ImgFile, { ext: ImgType, width, height, quality });
                        console.log(imgData);
                        ImgFile = imgData[0];
                        ImgType = 'webp';
                        dialogApi.setData({
                            preview: {
                                url: F.URL(ImgFile, 'webp')
                            },
                            filesize: (ImgFile.size / 1024).toFixed(0) + 'KB',
                            size: {
                                width: imgData[1] + 'px',
                                height: imgData[2] + 'px',
                            },
                            title: ImgFile.name
                        });
                        if (T.max_img_upload_size > ImgFile.size) {
                            dialogApi.setEnabled('size', !1);
                            dialogApi.setEnabled('submit', !0);
                        } else {
                            dialogApi.setEnabled('size', !0);
                            dialogApi.setEnabled('submit', !1);
                        }
                    }

                },
                async onChange(dialogApi, details) {
                    const data = dialogApi.getData();
                    console.log(dialogApi, details, data);
                    if (!ImgFile) return;
                    if (details.name == 'title') {
                        var title = F.getKeyName(data.title);
                        if (title.length > 1) {
                            ImgFile = new File([ImgFile], title + '.' + F.getExt(ImgFile.name), { type: ImgFile.type });
                            console.log(ImgFile);
                        }
                    }
                },
                async onSubmit(dialogApi, details) {
                    const data = dialogApi.getData();
                    if (ImgFile && ImgFile.size < 1024 * 1024 * 2 && ImgFile instanceof File) {
                        let post = I.post({
                            'attchfile': ImgFile
                        });
                        return T.CF('attach_ajax', post, (type, ...arg) => {
                            switch (type) {
                                case 'attachs':
                                case 'images':
                                    editor.insertContent(arg[0]);
                                    T.$('[name="attachid"]').value += arg[1] + ',';
                                    break;
                                case 'msg':
                                    editor.windowManager.alert(arg[0]);
                                    break;
                                case 'close':
                                    dialogApi.close();
                                    break;
                            }
                        });
                    }
                    console.log(dialogApi, details, data);
                },
                onCancel() {
                    ImgFile = null, ImgType = null, uploadFile = null;
                }
            });
        }
    });
    Object.assign(
        T.action, {
        async tinymce_load(data) {
            await T.CF('tinymce_worker_write');
            this.PostMessage({
                url: data.url,
                action: data.action,
                response: true,
            });
        },
        async tinymce_worker_write(mycache) {
            let mask = T.tinymce_mask();
            if (!mycache) mycache = await caches.open('XIUNOBBS');
            let result, files = await T.FetchItem({
                url: T.JSpath + 'zip/tinymce.zip',
                unpack: true,
                progress(a, b, c, d) {
                    mask[2].innerHTML = F.getname(a);
                    if (b) mask[1].value = parseInt(b);
                }
            });
            await I.Async(I.toArr(files).map(async entry => {
                let re = new Response(
                    new File([entry[1].buffer], F.getname(entry[0]), {
                        type: F.getMime(entry[0])
                    }), {
                    headers: {
                        status: 200,
                        'Content-Length': entry[1].byteLength
                    }
                }
                );
                if (entry[0].match(/tinymce\.min\.js/)) result = re;
                //写入至serverWorker缓存中
                await mycache.put(T.JSpath + entry[0], re);
            }));
            mask[0].remove();
            return result;
        },
        tinymce_attach_css() {
            if (T.$('#tinmce_attach_css')) return;
            let elm = T.$append(document.body, T.$ct('style', '', '', {
                id: 'tinmce_attach_css',
                type: 'text/css',
            }));
            elm.textContent = `
                button[title="${T.GL('Compress')}"]{
                    margin: 0px auto !important;
                    display: block !important;
                }`;
        }
    }
    );
    T.docload(async e => {
        if (T.$('.fastpost-textarea')) {
            T.tinymce_conf.selector = '.fastpost-textarea';
            await T.tinymce_files_add();
            tinymce.init(T.tinymce_conf);

        }
    });
}).call(Nenge);