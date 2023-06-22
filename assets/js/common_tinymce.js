
!(function(){
    var T = this,I=T.I,F=T.F;
    var lang = {
        'zh-CN':{
            'fonttext':'文本',
            'edit_loading':'编辑器加载中',
            'progress':'进度',
            'Submint':'发布',
            'Replay':'回帖',
            'attach':'附件',
            'attachfile':'上传附件',
            'attachimg':'上传图片',
            'attachunuser':'未使用附件',
            'uploading...':'上传中...',
            'request...':'等待服务器响应...'
        },
        'zh-TW':{
            'fonttext':'文本',
            'edit_loading':'编辑器載入中',
            'progress':'进程',
            'Submint':'发布',
            'Replay':'回帖'
        },
        'en':{
            'fonttext':'text',
            'edit_loading':'edit loading...'
        }
    };
    if(lang[T.language])Object.assign(T.lang,lang[T.language]);
    else Object.assign(T.lang,lang['en']);
    Object.assign(this,
        {
            tinymce_conf:{
                promotion: !1, //隐藏升级按钮
                branding: !1,//隐藏官网链接
                plugins: [
                    "accordion","advlist","anchor","autolink","autoresize","autosave","charmap","code","directionality","emoticons","fullscreen","image","insertdatetime","link","lists","media","nonbreaking","pagebreak","preview","quickbars","save","searchreplace","table","visualblocks","visualchars","wordcount"
                ],
                suffix: '.min',
                //icons: 'thin',\
                base_url: location.protocol+'//cdn.staticfile.org/tinymce/6.5.0/',
                skin: (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'oxide-dark' : 'oxide'),
                content_css: (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'default'),
                //skin_url:'https://cdn.staticfile.org/tinymce/6.5.0/skins/ui/oxide',
                language: 'zh-Hans',
                language_url:T.JSpath+'tinymce/langs/zh-Hans.js',
                toolbar: !1,
                toolbar_location: 'toolbar_sticky',
                selector: '.fastpost-textarea',
                menubar: 'file attachmenu insert edit format table tools view fonttext',
                min_height:400,
                mobile: {},
                menu: {
                    'file':{
                        title:T.GL('Replay'),
                        items:'submintbtn | preview | export print'
                    },
                    'fonttext': { title: T.GL('fonttext'), items: 'bold superscript subscript forecolor backcolor removeformat' },
                    'attachmenu':{
                        title:T.GL('attach'),
                        items:'attachfile | attachimg | attachunuser'
                    }
                },                
                setup: (editor) => {
                    editor.ui.registry.addMenuItem('submintbtn', {
                        text: T.GL('Submint'),
                        icon:'browse',
                        onAction: () => T.tinymce_submit(editor)
                    });
                    editor.ui.registry.addMenuItem('attachfile', {
                        text: T.GL('attachfile'),
                        icon:'browse',
                        onAction: () => T.tinymce_uploads(editor)
                    });
                    editor.ui.registry.addMenuItem('attachimg', {
                        text: T.GL('attachimg'),
                        icon:'browse',
                        onAction: () => T.tinymce_uploadimg(editor)
                    });
                    editor.ui.registry.addMenuItem('attachunuser', {
                        text: T.GL('attachunuser'),
                        icon:'browse',
                        onAction: () => T.tinymce_getattach(editor)
                    });
                }
            },
            async tinymce_load(){
                if(T.serviceActive){
                    let path = T.JSpath+'tinymce/tinymce.min.js';//此地址虚假,不存在文件
                    this.tinymce_conf.plugins.push("help");
                    this.tinymce_conf.menubar += " help";
                    this.tinymce_conf.base_url = T.JSpath+'tinymce';
                    return await T.addJS(path);
                }else{
                    await T.addJS(location.protocol+'//cdn.staticfile.org/tinymce/6.5.0/tinymce.min.js');
                }
                this.tinymce_conf.mobile.menubar = this.tinymce_conf.menubar;
            },
            async tinymce_write(mycache){
                let mask = T.tinymce_mask();
                if(!mycache)mycache = await caches.open('XIUNOBBS');
                let result,files = await T.FetchItem({
                    url:T.JSpath+'zip/tinymce.zip',unpack:true,
                    progress(a,b,c,d){
                        mask[2].innerHTML = F.getname(a);
                        if(b)mask[1].value = parseInt(b);
                    }
                });
                await I.Async(I.toArr(files).map(async entry=>{
                    let re = new Response(
                        new File([entry[1].buffer],F.getname(entry[0]),{type:F.getMime(entry[0])}),
                        {headers:{status:200,'Content-Length':entry[1].byteLength}}
                    );
                    if(entry[0].match(/tinymce\.min\.js/))result = re;
                    //写入至serverWorker缓存中
                    await mycache.put(T.JSpath+entry[0],re);
                }));
                mask[0].remove();
                return result;
            },
            tinymce_mask(){
                var mask = T.$ct('div','<label class="mask-label"><p>TinyMce'+T.GL('edit_loading')+'</p><b>'+T.GL('progress')+':</b><progress class="mask-progress" max="100" value="20"></progress></label>','mask-content');
                document.body.appendChild(mask);
                return [mask,T.$('progress',mask),T.$('b',mask)];
            },
            tinymce_submit(editor){
                if(T.$('.fastpost-form')){
                    let elm = T.$('.fastpost-form');
                    let post = I.post(elm);
                    post.set('message',editor.getContent());
                    post.set('doctype',0);
                    if(tinymce.activeEditor.plugins.fullscreen.isFullscreen())tinymce.activeEditor.ui.registry.getAll().menuItems.fullscreen.onAction();
                    T.ajax({
                        post,
                        url:elm.action,
                        type:'text',
                        success(a,b,c){
                            console.log(a,b,c);
                            window.kk = c;
                            tinymce.activeEditor.setContent('');
                        }
                    });
                }
            },
            tinymce_obj(){
                return tinymce.activeEditor;
            },
            tinymce_wm(){
                return this.tinymce_obj().windowManager;
            },
            tinymce_alert(str){
                return this.tinymce_wm().alert(str)
            },
            tinymce_confirm(msg,fn){
                return this.tinymce_wm().confirm(msg,fn);
            },
            tinymce_upload(fn,Accept,more){
                let input = T.$ce('input');
                input.type='file';
                if(Accept)input.accept = Accept;
                if(more)input.multiple = !0;
                input.onchange = e=>{
                    fn(e.target.files);
                    input.remove();
                };
                input.click();
                return input;
            },
            tinymce_uploads(editor){
                let input = T.tinymce_upload(async files=>{
                    if(files.length>0){
                        let mask = T.CF('mask');
                        let zipblob = await T.toZip(files,(current, total,filename)=>{
                            console.log(current, total,filename);
                            mask[1].value = T.I.PER(current, total,!0);
                            mask[2].innerHTML = filename;
                        });
                        let zipFile = new File([zipblob],files[0].name+'.zip',{type:F.getMime('zip')});
                        let post = I.post({'attachFile':zipFile});
                        T.ajax({
                            url:location.href,
                            post,
                            postProgress(current,total){
                                mask[1].value = T.I.PER(current, total,!0);
                                mask[2].innerHTML = T.GL('uploading...');
                            },
                            progress(current,total){
                                mask[1].value = T.I.PER(current, total,!0);
                                mask[2].innerHTML = T.GL('request...');
                            },
                            success(text,headers){
                                mask[0].remove();
                                console.log(text,headers);
                            },
                            error(){
                                mask[0].remove();
                            }
                        });
                        //   T.download('abc.zip',zipblob);
                    }
                },'*',!0);
            },
            tinymce_uploadimg(editor){
                let input = T.tinymce_upload(async files=>{
                        //let [width,height,mime] = await T.getImageSize(files[0])
                        let imgfile = await T.toWebp(files[0]);
                        //else{
                        //   if(!T.image2webp_load)await T.addJS(T.JSpath+'common_webp.js');
                        //    imgfile = new File([await T.image2webp(files[0],width,height)],files[0].name,{type:F.getMime('webp')});
                        //}
                        var imgs= new Image();imgs.src=F.URL(imgfile);
                        document.body.appendChild(imgs);
                        console.log(imgfile);
                        return ;
                        let post = I.post({'attachImages':imgfile});
                        T.ajax({
                            url:location.href,
                            post,
                            postProgress(current,total){
                                mask[1].value = T.I.PER(current, total,!0);
                                mask[2].innerHTML = T.GL('uploading...');
                            },
                            progress(current,total){
                                mask[1].value = T.I.PER(current, total,!0);
                                mask[2].innerHTML = T.GL('request...');
                            },
                            success(text,headers){
                                mask[0].remove();
                                console.log(text,headers);
                            },
                            error(){
                                mask[0].remove();
                            }
                        });
                },'image/*');
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
        T.action,{
            async tinymce_load(data){
                await T.tinymce_write();
                this.PostMessage({
                    url:data.url,
                    action:data.action,
                    response:true,

                });
            }
        }
    );
    T.docload(e=>{
        if(T.$('.fastpost-textarea')){
            T.tinymce_conf.selector = '.fastpost-textarea';
                T.tinymce_load().then(e=>{
                    tinymce.init(T.tinymce_conf);
                });

        }
    });
}).call(Nenge);