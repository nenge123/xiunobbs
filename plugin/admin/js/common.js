(function(){
    let T=this;
    Object.assign(T.action,{
        openMenu(){
            let menu = T.$('.xn-main .container-left .menu-open');
            if(menu){
                T.on(menu,T.I.mobile?'touchstart':'click',e=>{
                    menu.parentNode.parentNode.classList.toggle('active');
                });
            }
        },
        BindEvent(){
            T.$$('[ajax]').forEach(elm=>{
                let ajax = T.attr(elm,'ajax');
                let href = T.attr(elm,'href');
                elm.removeAttribute('ajax');
                if(href){
                    T.on(elm,T.I.mobile?'touchend':'click',e=>{
                        T.stopProp(e);
                        T.runaction('ajax_'+ajax,[elm]);
                        return false;
                    },false);
                }else if(elm.tagName=='FORM'){
                    T.on(elm,'submit',e=>{
                        T.stopEvent(e);
                        T.runaction('ajax_'+ajax,[elm]);
                        return false;
                    });
                }else if(T.attr(elm,'type')=='submit'){
                    var pelm = elm.parentNode;
                    while(pelm.tagName!='FORM'){
                        pelm = pelm.parentNode;
                    }
                    T.on(pelm,'submit',e=>{
                        T.stopEvent(e);
                        T.runaction('ajax_'+ajax,[pelm]);
                        return false;
                    });
                }
            });
            T.$$('[xn-action]').forEach(
                elm=>{
                    let act = T.attr(elm,'xn-action');
                    elm.removeAttribute('xn-action');
                    T.on(elm,T.I.mobile?'touchend':'click',e=>{
                        T.stopProp(e);
                        T.runaction('click_'+act,[elm]);
                        return false;
                    },false);
                }
            )
        },
        ajax_getPage(elm){
            let href = T.attr(elm,'href'),R=T.$('.container-right').classList,LI=elm.parentNode.parentNode,addActive=()=>{            
                T.I.toArr(LI.parentNode.children,x=>{
                    x.classList[x==LI?'add':'remove']('active');
                });
            };
            if(href){
                if(location.href.indexOf(href)!=-1){
                    T.$('.xn-main').classList.toggle('active');
                    return addActive();
                };
                R.add('active');
                T.ajax({
                    url:href,
                    type:'text',
                    success(text){
                        R.remove('active');
                        if(text){
                            addActive();
                            T.$('.container-right').innerHTML = text;
                            T.runaction('BindEvent');
                            history.replaceState('','',href);
                        }

                    },
                    error(){
                        R.remove('active');

                    }
                })
            }
        },
        ajax_basePost(elm,e,post,url){
            T.$('.container-right').classList.add('active');
            T.ajax({
                url:url?url:T.attr(elm,'action'),
                post:post?post:T.I.post(elm),
                success(text){
                    T.$('.container-right').classList.remove('active');
                    T.$('.container-right').innerHTML = text;
                    T.runaction('BindEvent');
                },
                error(){
                    T.$('.container-right').classList.remove('active');

                }
            })
        },
        ajax_deleteForum(elm){
            let href = T.attr(elm,'href')||location.href,data=T.I.toObj(elm.dataset);
            let yes = window.confirm(T.getLang('are you Delete?'));
            if(!yes) return;
            T.runaction('ajax_basePost',[elm,null,T.I.post(data),href]);
        },
        ajax_jsonPost(){
            T.$('.container-right').classList.add('active');
            T.ajax({
                url:T.attr(elm,'action'),
                post:T.I.post(elm),
                type:'json',
                success(text){
                    elm.classList.remove('active');
                    console.log(text)
                },
                error(){
                    T.$('.container-right').classList.remove('active');

                }
            })

        },
        click_smtpDelete(elm){
            let pelm = elm.parentNode;
            while(pelm.tagName!='TR'){
                pelm = pelm.parentNode;
            }
            pelm.remove();
        },
        click_addForum(elm){
            let pelm = elm.parentNode,helm = T.docElm(T.$('script[type="text/template"]').textContent.trim()).body.firstChild;
            pelm.parentNode.insertBefore(helm,pelm.nextElementSibling);
        }
    });
    T.docload(e=>{
        T.runaction('BindEvent');
        T.runaction('openMenu');
    });
}).call(Nenge);