
!(function(){
    var T = this,I=T.I,F=T.F;
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
                base_url: 'https://cdn.staticfile.org/tinymce/6.5.0/',
                skin: 'oxide',
                //skin_url:'https://cdn.staticfile.org/tinymce/6.5.0/skins/ui/oxide',
                language: 'zh-Hans',
                language_url:T.JSpath+'tinymce/langs/zh-Hans.js',
                toolbar: !1,
                toolbar_location: 'bottom',
                selector: '.fastpost-textarea',
                menubar: 'file insert edit format table tools fonttext',
                mobile: {},
                menu: {
                    'fonttext': { title: T.GL('fonttext'), items: 'bold superscript subscript forecolor backcolor removeformat' }
                }
            },
            async tinymce_load(){
                if('serviceWorker' in navigator){
                    //worker is open
                    let mycache = await caches.open('XIUNOBBS');
                    let path = T.JSpath+'tinymce/tinymce.min.js';//此地址虚假,不存在文件
                    let response = await mycache.match(path);
                    this.tinymce_conf.plugins.push("help");
                    this.tinymce_conf.menubar += " help";
                    this.tinymce_conf.base_url = T.JSpath+'tinymce';
                    if(!response){
                        //下载并且用 (gildas-lormeau/zip.js)解压
                        await this.tinymce_down('tinymce.zip',mycache);
                    }
                    await T.addJS(path);
                }else{
                    await T.addJS('https://cdn.staticfile.org/tinymce/6.5.0/tinymce.min.js');
                }
                this.tinymce_conf.mobile.menubar = this.tinymce_conf.menubar;
            },
            async tinymce_down(zip,mycache){
                let files = await T.FetchItem({url:T.JSpath+'zip/'+zip,unpack:true});
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