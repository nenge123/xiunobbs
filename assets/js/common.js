!(function(exports){
    class NengeModule{
        /**
         * 简易AJAX请求
         * @param {object} ARG 
         *  ARG = {href,progress,success,body,api,ajax}
         * @returns {Promise}
         */
        ajax(ARG){
            return new Promise(back=>{
                const request = new XMLHttpRequest;
                request.on('readystatechange', e => {
                    if(request.readyState===request.HEADERS_RECEIVED){
                        let header = request.getResponseHeader('content-type');
                        if(header){
                            if(header.indexOf('json')!=-1){
                                request.responseType = 'json';
                            }else if(header.indexOf('html')!=-1){
                                request.responseType = 'document';
                            }else if(header.indexOf('application')!=-1&&header.indexOf('javascript')==-1){
                                request.responseType = 'arraybuffer';
                            }
                        }
                    }else if(request.readyState === request.DONE){
                        back(request.response);
                        if(ARG.success)ARG.success(request.response,request);
                    }
                });
                request.on('progress',e=>ARG.progress instanceof Function&&ARG.progress(e.loaded, e.total));
                request.upload.on('progress',e=>ARG.upload instanceof Function&&ARG.upload(e.loaded, e.total));
                let method = 'GET';
                if(ARG.body)method = 'POST';
                request.open(method,ARG.href);
                if(ARG.ajax)request.setRequestHeader('ajax-fetch','1');
                if(ARG.api)request.setRequestHeader('ajax-api',ARG.api);
                if(ARG.headers)Object.entries(ARG.headers).forEach(entry=>request.setRequestHeader(entry[0],entry[1]));
                request.send(ARG.body);
            });
        }
        /**
         * 尝试请求HTML并解析为modal
         * 解析成功返回modal的 Element对象
         * 如果请求结果为json则返回根据{type}的回调函数
         * @param {*} ARG 
         * @returns 
         */
        async modal_ajax(ARG){
            let ajax = await N.ajax(ARG);
            if(!ajax)return;
            if(ajax instanceof Document){
                let modal = ajax.body.firstChild;
                if(modal instanceof Element&&modal.classList.contains('modal')){
                    document.body.appendChild(modal);
                    N.paser(modal);
                    $(modal).modal('show');
                    ajax.documentElement.remove();
                    return modal;
                }
            }else if(ajax.constructor === Object){
                if(ajax.type&&ARG[ajax.type]) return ARG[ajax.type](ajax);
            }
        }
        /**
         * feedback回调函数处理
         * @param {*} data 回调的数据
         * @param {*} formElm 执行对象
         */
        async modal_feedback(data,formElm){
            if(data.valid)Array.from(Object.entries(data.valid),entry=>{
                let str = '*[name="'+entry[0]+'"]';
                let feedElm = formElm.querySelector(str+'+.invalid-feedback');
                let inputElm = formElm.querySelector(str);
                if(entry[1] !== true){
                    inputElm.classList.add('is-invalid');
                    inputElm.classList.remove('is-valid');
                    feedElm.innerHTML = entry[1];
                }else{
                    inputElm.classList.remove('is-invalid');
                    inputElm.classList.add('is-valid');
                    feedElm.innerHTML = '';
                }
            });
            if(data.value)Array.from(Object.entries(data.value),entry=>{
                let inputElm = formElm.querySelector('input[name="'+entry[0]+'"]');
                if(inputElm){
                    inputElm.value = entry[1];
                }
            });
            if(data.alert){
                alert(data.alert);
            }
            if(data.success){
                formElm.querySelector('.modal-body').innerHTML = data.success;
                let ajaxid = formElm.getAttribute('data-ajaxid');
                if(ajaxid){
                    let button = formElm.querySelector('[type="submit"]');
                    if(button)button.hidden=true;
                    if(button.nextElementSibling)button.nextElementSibling.hidden=false;
                    else setTimeout(()=>$(ajaxid).modal('hide'),3000);
                }

            }

        }
        /**
         * 储存data-ajax处理
         */
        action = {
            elm_form(elm){
                elm.on('submit',async function(e){
                    e.preventDefault();
                    e.stopPropagation();
                    if(this.disable)return;
                    this.disabled = true;
                    let target = e.target;
                    let href = target.action;
                    let method = target.method||'get';
                    let post = method&&method.toLowerCase()=='post'?new FormData(target):null;
                    let ajax = await N.modal_ajax({
                        href,
                        ajax:true,
                        body:post,
                        success:(a,b)=>{
                            //对表单更新
                            if(b&&b instanceof XMLHttpRequest){
                                let hash = b.getResponseHeader('content-hash');
                                let time = b.getResponseHeader('content-time');
                                let hashelm = this.querySelector('[name="hash"]')
                                if(hashelm)hashelm.value = hash;
                                let timeelm = this.querySelector('[name="time"]')
                                if(timeelm)timeelm.value = parseInt(time);
                                let postid = b.getResponseHeader('content-post');
                                if(postid){
                                    let message = this.querySelector('[name="message"]');
                                    if(message)message.value='';
                                }
                            }
                        }
                    },this);
                    this.disabled = false;
                });
            },
            elm_a(elm){
                elm.on('click',async function(e){
                    e.preventDefault();
                    e.stopPropagation();
                    await N.modal_ajax({
                        ajax:true,
                        href:this.href,

                    });

                });
            },
            api_login(elm){
                elm.on(elm instanceof HTMLFormElement?'submit':'click',async function(e){
                    e.preventDefault();
                    e.stopPropagation();
                    if(this.disable)return;
                    this.disabled = true;
                    let elmf = e.target;
                    let bodydata;
                    if(elmf instanceof HTMLFormElement){
                        bodydata = new FormData(elmf);
                    }
                    let ajax = await N.modal_ajax({
                        href:'',
                        ajax:true,
                        api:'login',
                        body:bodydata,
                        feedback:(data)=>N.modal_feedback(data,elmf),
                    });
                    if(ajax instanceof Element){
                        //当返回一个新的modal对象时 尝试隐藏之前的
                        let ajaxid = this.getAttribute('data-ajaxid');
                        if(ajaxid){
                            $(ajaxid).modal('hide');
                        }
                    }
                    this.disabled = false;
                });

            }

        }
        method = {
            refresh(){
                if(this.href)return location.href=this.href;
                location.reload();
            }
        };
        paser(dom){
            if(!dom)dom = document.body;
            Array.from(dom.querySelectorAll('[data-ajax]'),elm=>{
                let tag = 'elm_'+elm.tagName.toLowerCase();
                let ajax = elm.getAttribute('data-ajax').split('-');
                if(ajax[0]=='api') return this.action['api_'+ajax[1]](elm);
                if(this.action[tag])return this.action[tag](elm);
                console.log(tag);
            });
            Array.from(dom.querySelectorAll('[data-method]'),elm=>{
                let method = elm.getAttribute('data-method');
                elm.on('click',this.method[method]);
            });
            Array.from(dom.querySelectorAll('[data-timeset]'),elm=>{
                elm.text = elm.textContent;
                elm.disabled = true;
                let now = Math.floor(Date.now()/1000);
                let timeset = Array.from(elm.getAttribute('data-timeset').split(','),v=>parseInt(v));
                elm.textContent = elm.text+'('+(timeset[0]-now)+')';
                elm.timer = setInterval(()=>{
                    now = Math.floor(Date.now()/1000);
                    if(timeset[0]>now){
                        elm.disabled = true;
                        elm.textContent = elm.text+'('+(timeset[0]-now)+')';
                    }else if(timeset[1]){
                        if(timeset[1]-now>0){
                            elm.disabled = false;
                            elm.textContent = elm.text+'('+(now-timeset[1])+')';
                        }else{
                            elm.disabled = true;
                            elm.textContent = this.getLang('thread_attach_longtime');
                            clearInterval(elm.timer);
                        }
                    }else{
                        elm.disabled = false;
                        clearInterval(elm.timer);
                    }

                },1000);
            });
        }
        getLang(name,param){
            return 'ghghf';
        }
        constructor(){
            Object.assign(EventTarget.prototype, {
                /**
                 * 绑定事件
                 * @param {*} evt 
                 * @param {*} fun 
                 * @param {*} opt 
                 * @returns 
                 */
                on(evt, fun, opt) {
                    return this.addEventListener(evt, fun, opt), this;
                },
                /**
                 * 解绑事件
                 * @param {*} evt 
                 * @param {*} fun 
                 * @param {*} opt 
                 * @returns 
                 */
                un(evt, fun, opt) {
                    return this.removeEventListener(evt, fun, opt), this;
                },
                /**
                 * 绑定一次事件
                 * @param {*} evt 
                 * @param {*} fun 
                 * @param {*} opt 
                 * @returns 
                 */
                once(evt, fun, opt) {
                    return this.on(evt, fun, Object.assign({
                        passive: !1,
                        once: !0,
                    }, opt === true ? { passive: !0 } : opt || {})), this;
                },
                /**
                 * 触发自定义事件
                 * @param {*} evt 
                 * @param {*} detail 
                 */
                toEvent(evt, detail) {
                    return this.dispatchEvent(typeof evt=='string'?new CustomEvent(evt, { detail }):evt), this;
                }
            });
            this.paser();
        }
    }
    const N = new NengeModule;
    Object.defineProperties(exports,{
        Nenge:{
            get:()=>N,
        }
    });
})(self);