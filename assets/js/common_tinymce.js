
!(function () {
    var T = this, I = T.I, F = T.F;
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
            'uploading...': '上传中...',
            'request...': '等待服务器响应...'
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
    Object.assign(this,
        {
            max_upload_size:1887436,
            tinymce_conf: {
                promotion: !1, //隐藏升级按钮
                branding: !1,//隐藏官网链接
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
                    'fonttext': { title: T.GL('fonttext'), items: 'bold superscript subscript forecolor backcolor removeformat' },
                    'attachmenu': {
                        title: T.GL('attach'),
                        items: 'attachfile | attachimg | attachunuser'
                    }
                },
                setup: (editor) => {
                    editor.ui.registry.addMenuItem('submintbtn', {
                        text: T.GL('Submint'),
                        icon: 'browse',
                        onAction: () => T.tinymce_submit(editor)
                    });
                    editor.ui.registry.addMenuItem('attachfile', {
                        text: T.GL('attachfile'),
                        icon: 'browse',
                        onAction: () => T.tinymce_uploads(editor)
                    });
                    editor.ui.registry.addMenuItem('attachimg', {
                        text: T.GL('attachimg'),
                        icon: 'browse',
                        onAction: () => T.tinymce_open_uploadimg(editor)
                    });
                    editor.ui.registry.addMenuItem('attachunuser', {
                        text: T.GL('attachunuser'),
                        icon: 'browse',
                        onAction: () => T.tinymce_getattach(editor)
                    });
                }
            },
            async tinymce_load() {
                if (T.serviceActive) {
                    let path = T.JSpath + 'tinymce/tinymce.min.js';//此地址虚假,不存在文件
                    this.tinymce_conf.plugins.push("help");
                    this.tinymce_conf.menubar += " help";
                    this.tinymce_conf.base_url = T.JSpath + 'tinymce';
                    return await T.addJS(path);
                } else {
                    await T.addJS(location.protocol + '//cdn.staticfile.org/tinymce/6.5.0/tinymce.min.js');
                }
                this.tinymce_conf.mobile.menubar = this.tinymce_conf.menubar;
            },
            async tinymce_write(mycache) {
                let mask = T.tinymce_mask();
                if (!mycache) mycache = await caches.open('XIUNOBBS');
                let result, files = await T.FetchItem({
                    url: T.JSpath + 'zip/tinymce.zip', unpack: true,
                    progress(a, b, c, d) {
                        mask[2].innerHTML = F.getname(a);
                        if (b) mask[1].value = parseInt(b);
                    }
                });
                await I.Async(I.toArr(files).map(async entry => {
                    let re = new Response(
                        new File([entry[1].buffer], F.getname(entry[0]), { type: F.getMime(entry[0]) }),
                        { headers: { status: 200, 'Content-Length': entry[1].byteLength } }
                    );
                    if (entry[0].match(/tinymce\.min\.js/)) result = re;
                    //写入至serverWorker缓存中
                    await mycache.put(T.JSpath + entry[0], re);
                }));
                mask[0].remove();
                return result;
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
            tinymce_upload(fn, Accept, more) {
                let input = T.$ce('input');
                input.type = 'file';
                if (Accept) input.accept = Accept;
                if (more) input.multiple = !0;
                input.onchange = e => {
                    fn(e.target.files);
                    input.remove();
                };
                input.click();
                return input;
            },
            tinymce_uploads(editor) {
                editor.windowManager.open({
                    title:'Upload',
                    body: {
                        type:'panel',
                        items:[
                            {
                                type: 'input', // component type
                                name: 'password', // identifier
                                inputMode: 'text',
                                label: '密码', // text for the label
                                placeholder: 'example', // placeholder text for the input
                                enabled:!0, // disabled state
                              },
                              {
                                type: 'htmlpanel', // an HTML panel component
                                html: '文件会自动转换为压缩文件,并且你可以设置密码.附件支持在线预览内部图片等.'
                              }
                        ]
                    },
                    buttons: [
                        {
                            type: 'cancel',
                            name:'cancel',
                            text: 'Cancel',
                            enabled:!0,
                        },
                        {
                            type: 'submit',
                            name:'submit',
                            text: 'Upload',
                            enabled:!0,
                        }
                    ],
                });
            },
            tinymce_open_uploadimg(editor) {
                var ImgFile,ImgType,uploadFile;
                editor.windowManager.open({
                    title:'Uploading image',
                    body: {
                        type: 'panel', // The root body type - a Panel or TabPanel
                        items: [
                            {
                                type: 'imagepreview', // component type
                                name: 'preview', // identifier
                                height: '280px', // optional CSS height to constrain the image preview to
                            },{
                                type: 'slider', // component type
                                name: 'quality', // identifier
                                label: 'Resize',
                                min: 0, // minimum value
                                max: 100 // maximum value
                              },
                            {
                                type: 'sizeinput', // component type
                                name: 'imgwh', // identifier
                                label: 'Dimensions',
                                enabled: false // enabled state
                            },{
                                type: 'input', // component type
                                name: 'imgSize', // identifier
                                inputMode: 'text',
                                label: 'Image...', // text for the label
                                placeholder: 'example', // placeholder text for the input
                                enabled: false, // disabled state
                              },{
                                type: 'input', // component type
                                name: 'imgtitle', // identifier
                                inputMode: 'text',
                                label: 'Image title', // text for the label
                                placeholder: 'example', // placeholder text for the input
                                enabled: false, // disabled state
                              }
                        ]
                    },
                    buttons: [
                        {
                            type: 'custom',
                            name: 'uploadimg',
                            text: "Browse for an image",
                        },
                        {
                            type: 'cancel',
                            name:'cancel',
                            text: 'Cancel',
                            enabled:!0,
                        },
                        {
                            type: 'submit',
                            name:'imguploadbtn',
                            text: 'Upload',
                            enabled:!1,
                        }
                    ],
                    initialData: {
                        quality: 85,
                        imgwh:{width:'1',height:'1'}
                    },
                    onAction(dialogApi, details) {
                        const apidata = dialogApi.getData();
                        if (details.name == 'uploadimg') {
                            T.tinymce_upload(async files => {
                                ImgFile = files[0];
                                var quality = parseInt(apidata.quality);
                                var imgData;
                                let ext = await F.CheckExt(ImgFile);
                                ImgType = ext;
                                if (ext == 'gif') {
                                    if(ImgFile.size>1024*1024*1.5){
                                        dialogApi.setEnabled('imguploadbtn',!1);
                                        //dialogApi.setEnabled('quality',!1);
                                        dialogApi.setData({
                                            preview: { url: F.URL(ImgFile,ext) },
                                            imgSize:(ImgFile.size/1024).toFixed(0)+'KB'+T.GL('too big')
                                        });
                                        return;
                                    }
                                    imgData = await T.im2webp(ImgFile,quality);
                                } else {
                                    imgData = await T.encode_webp(ImgFile,quality);
                                }
                                if(apidata.preview.url)F.removeURL(apidata.preview.url);
                                var width = imgData[1];
                                var height = imgData[2];
                                uploadFile = imgData[0];
                                dialogApi.setData({
                                    preview: { url: F.URL(imgData[0],'webp') },
                                    imgwh:{width:width+"",height:height+""},
                                    imgSize:(imgData[0].size/1024).toFixed(0)+'KB',
                                    imgtitle:uploadFile.name
                                });
                                //dialogApi.setEnabled('quality',!0);
                                dialogApi.setEnabled('imgtitle',!0);
                                dialogApi.setEnabled('imguploadbtn',T.max_upload_size>imgData[0].size);
                            }, 'image/*');
                        }

                    },
                    async onChange(dialogApi, details){
                        const data = dialogApi.getData();
                        console.log(dialogApi, details,data);
                        if(details.name=='quality'){
                            if(ImgFile){
                                var quality = data.quality.toString();
                                var ImgData;
                                let ext = ImgType;
                                if (ext == 'gif') {
                                    ImgData = await T.im2webp(ImgFile,quality);
                                } else {
                                    ImgData = (await T.encode_webp(ImgFile,quality));
                                }
                                if(data.preview.url)F.removeURL(data.preview.url);
                                uploadFile = ImgData[0];
                                dialogApi.setData({
                                    preview: { url: F.URL(ImgData[0],'webp') },
                                    imgSize:(ImgData[0].size/1024).toFixed(0)+'KB'
                                });
                                dialogApi.setEnabled('imguploadbtn',T.max_upload_size>ImgData[0].size);

                            }
                        }
                    },
                    async onSubmit(dialogApi, details){
                        const data = dialogApi.getData();
                        if(uploadFile&&uploadFile.size<1024*1024*2&&uploadFile instanceof File){
                            let post = I.post({ 'attchfile': uploadFile });
                            let mask = T.progress_mask();
                            T.ajax({
                                url: location.href,
                                post,
                                postProgress(per,current, total) {
                                    mask[1].value = parseInt(per);
                                    mask[2].innerHTML = T.GL('uploading...');
                                },
                                progress(per,current, total) {
                                    mask[1].value = parseInt(per);
                                    mask[2].innerHTML = T.GL('request...');
                                },
                                success(text, headers) {
                                    mask[0].remove();
                                    if(headers['content-type'] == 'application/json'){
                                        I.toArr(JSON.parse(text),entry=>{
                                            if(!entry[1]){
                                                editor.insertContent('[attach]'+entry[0]+'[/attach]');
                                            }else{
                                                editor.insertContent('<img src="'+entry[1][0]+'" alt="'+entry[1][1]+'"/>');
                                            }
                                            T.$('[name="attachid"]').value+=entry[0]+',';
                                        });
                                        dialogApi.close();
                                    }
                                    console.log(text, headers);
                                },
                                error() {
                                    mask[0].remove();
                                    dialogApi.close();
                                }
                            });

                        }
                        console.log(dialogApi, details,data);
                    },
                    onCancel(){
                        ImgFile=null,ImgType=null,uploadFile=null;
                    }
                });
            }
            /*
            tinymce.activeEditor.windowManager.open({
  title: 'Dialog Title', // The dialog's title - displayed in the dialog header
  body: {
    type: 'panel', // The root body type - a Panel or TabPanel
    items: [ // A list of panel components
      {
        type: 'htmlpanel', // an HTML panel component
        html: 'Panel content goes here.'
      }
    ]
  },
  buttons: [ // A list of footer buttons
    {
      type: 'submit',
      text: 'OK'
    }
  ]
}); */

        }
    );
    Object.assign(
        T.action, {
        async tinymce_load(data) {
            await T.tinymce_write();
            this.PostMessage({
                url: data.url,
                action: data.action,
                response: true,

            });
        }
    }
    );
    T.docload(e => {
        if (T.$('.fastpost-textarea')) {
            T.tinymce_conf.selector = '.fastpost-textarea';
            T.tinymce_load().then(e => {
                tinymce.init(T.tinymce_conf);
            });

        }
    });
}).call(Nenge);