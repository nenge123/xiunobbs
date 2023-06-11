<?php
defined('XIUNO')||die('return to <a href="">Home</a>');
$myapp->data['title'] = $language['thread_not_exists'];
if(!empty($router_value)&&is_numeric($router_value)){
    $thread = Nenge\DB::t('thread')->fetch(array('tid'=>$router_value));
    if(!empty($thread)&&!empty($myapp->data['forumlist'][$thread['fid']])){
        $forum = $myapp->data['forumlist'][$thread['fid']];
        $access = $myapp->data['access'];
        if(!empty($myapp->data['forum_access'][$forum['fid']][$myapp->data['gid']])){
            $access = array_merge($myapp->data['forum_access'][$forum['fid']][$myapp->data['gid']]);
        }
        if(!empty($access['allowthread'])){
            #无权访问帖子
            $myapp->data['title'] = $thread['subject'];
            $only = !1;
            $order = 'ASC';
            $limit = 40;
            $page = 1;
            if(empty($_GET['page'])){
                $page = !empty($myapp->data['router'][2])&&is_numeric($myapp->data['router'][2])?intval($myapp->data['router'][2]):1;
                $order = !empty($myapp->data['router'][3])&&$myapp->data['router'][2]=='DESC'?'DESC':'ASC';

            }else{
                $page = intval($_GET['page']);
                $page = $page?:1;
                if(!empty($_GET['order'])){
                    $order = $_GET['order']=='DESC'?'DESC':'ASC';
                }
            }
            if(!empty($_GET['only'])){
                $only = $thread['uid'];
            }
            $url_order = '-'.$order;
            $order = strtoupper($order);
            $postlist = Nenge\DB::t('post')->page_by_tid($thread['tid'],$page,$order,$only,$limit);
            if($order == 'ASC'){
                $floor = ($page-1)*40+1;
            }else{
                $floor = $thread['posts']+2;
            }
        }else{
            $thread = null;
        }
    }
}
include $myapp->template('thread');
/*
$thread_id = intval($myapp->router->act);
if(!$thread_id){
    $myapp->exit($language['thread_not_exists']);
}
$thread = $myapp->DB('FetchRow','thread',array('tid'=>$thread_id));
if(empty($thread)){
    $myapp->exit($language['thread_not_exists']);
}
$thread_uid = $thread['uid'];
$thread_attach = intval($thread['images'])+intval($thread['files']);
$forum_id = $thread['fid'];
$forum = $myapp['forumlist'][$forum_id];
$forum_access = $myapp->data['forumlist'][$forum_id]['access'];
if(empty($forum_access['allowread'])){
    $myapp->exit($language['insufficient_visit_forum_privilege']);
}
if(!empty($forum['modlist'][$uid])){
    $modgroup = $myapp['grouplist'][4];
}elseif(in_array($gid,array(1,2))){
    $modgroup = $myapp['grouplist'][$gid];
}
$allowupdate = empty($modgroup['allowupdate'])?false:true;
$allowdelete  = empty($modgroup['allowdelete'])?false:true;
#设置默认页数
if(!empty($myapp['router'][0])){
    $page = intval($myapp['router'][0]);
}
$page = empty($page)?$page:1;
#设置默认 查询排序
if(!empty($myapp->router->data[3])){
    $scriptOrder = $myapp->router->data[3] == 'new'?'DESC':'ASC';
}else{
    $scriptOrder = $myapp['settings']['thread_order'];
}
#帖子 排序方式
$postOrder = array('create_date'=> $scriptOrder);
$threadQuery = array(
    'order'=>'`isfirst` DESC,`create_date` '.$scriptOrder
);
if(!empty($_GET['only'])&&$_GET['only']>0){
    $threadQuery['where']['uid'] = intval($_GET['only']);
}
if($myapp->data['method']=='POST'){
    if(!empty($_POST['hash'])&&$_POST['hash']==$myapp->data['hash']){
        if(!empty($_POST['action'])){
            $action = explode('-',$_POST['action']);
            if($action[0]=='post'){
                if(empty($forum_access['allowpost'])){
                    $myapp->json(array('code'=>-1,'msg'=>'privilege_post'));
                }
                if(!empty($_POST['message'])){
                    
                }
            }
        }
    }elseif(!empty($_POST['page'])){
        list($postlist,$postFirst,$postAttach)= $myapp->F('get_postlist',$thread,intval($_POST['page']),$myapp->data['settings']['thread_size'],$threadQuery);
        if(empty($postlist)){
            $myapp->json(array(
                'code'=>-1,
                'html'=>''
            ));

        }
        ob_start();
        include $myapp->getTemplate('list/postreply');
        $html = ob_get_contents();
        ob_clean();
        $myapp->json(array(
            'code'=>0,
            'html'=>$html
        ));
    }
    $errorPost = array(
        'title'=>$language['unknow_action'],
        'html'=>$language['unknow_action_txt'],
        'hidden'=>array(
            "footer"=>true
        )
    );
    /*
    #处理快捷回复
    if($myapp->POST('hash') != md5($scriptHash)){
        #验证表单请求
        $myapp->json(array('error'=>$language['unknow_post']));
    }
    if(!empty($_POST['deletepid'])){
        #删除帖子
        if(empty($myapp->data['user']['uid'])){
            $myapp->json(array('error'=>$language['please_login']));
        }
        #$post = $myapp->DB('FetchRow','post',array('pid'=>intval($_POST['deletepid'])));
        if($allowdelete){
            $result = $myapp->F('delete_post',intval($_POST['deletepid']),$thread_id,$forum_id);
        }elseif($group['allowdelete']){
            $post = $myapp->DB('FetchRow','post',array('pid'=>intval($_POST['deletepid'])));
            if($post['uid']==$myapp->data['user']['uid']){
                $result = $myapp->F('delete_post',intval($_POST['deletepid']),$thread_id,$forum_id,$post);
            }
        }
        if(!empty($result['line'])){
            $myapp->json(array(
                'pid'=>$_POST['deletepid'],
                'error'=>$language['post_delete'],
            ));    
        }else{
            $myapp->json(array('error'=>$language['insufficient_delete_privilege']));
        }
    }elseif(isset($_POST['message'])){
        #快捷回帖处理
        if (empty($_POST['message'])) {
            #验证表单 message
            $myapp->json(array('error' => $language['empty_message']));
        }
        if (empty($forum_access['allowpost'])) {
            #验证 是否有权限发帖
            $myapp->json(array('error' => $language['privilege_post']));
        }
        #是否匿名回帖
        $quick_uid = empty($myapp->data['user']) ? 0 : $myapp->data['user']['uid'];
        if (!empty($myapp['settings']['post_limit'])) {
            #发帖限制
            $quick_query = [];
            if (empty($quick_uid)) {
                #匿名用户 会大幅限制为整个论坛发帖
                $quick_query = array('userip' => $myapp->data['longip']);
            } else {
                #注册用户
                if (!empty($myapp['settings']['post_limitmode'])) {
                    if ($myapp['settings']['post_limitmode'] == 'tid') $quick_query['tid'] = $thread_id;
                    if ($myapp['settings']['post_limitmode'] == 'fid') $quick_query['fid'] = $$forum_id;
                }
                $quick_query['uid'] = $quick_uid;
            }
            $last_create_date = $myapp->DB('FetchValue', 'post', '`create_date`', $quick_query, array('order' => array('create_date' => 'DESC')));
            $quick_limit_time = intval($myapp['settings']['post_limit']) + intval($last_create_date) - $myapp->data['time'];
            if ($quick_limit_time > 0) {
                $myapp->json(array('error' => $language->lang('post_limit_time', array('time' => $quick_limit_time))));
            }
        }
        $quick_quotepid = 0;
        if(!empty($_POST['quotepid'])){
            #引用pid 检测
            $quotepid = $myapp->DB('FetchValue','post','`pid`',array('pid'=>intval($_POST['quotepid']),'tid'=>$thread_id));
            $quick_quotepid = empty($pid)?0:intval($quotepid);
        }
        #过滤message
        $quick_message = $myapp->F('message_filter', $_POST['message'], 1);
        $quick_postdata = array(
            'tid' => $thread_id,
            'uid' => $quick_uid,
            'isfirst' => 0,
            #1 为纯文本
            'doctype' => 1,
            #考虑新字段 楼层
            #'index' => $thread['posts'] + 2,
            'quotepid' => $quick_quotepid,
            'create_date' => $myapp->data['time'],
            'userip' => $myapp->data['longip'],
            'message' => $quick_message
        );
        $result = $myapp->DB('Insert', 'post', $quick_postdata);
        #是否插入成功
        if (!empty($result['lastid'])) {
            $quick_pid = intval($result['lastid']);
            #更新主题记录
            $myapp->DB(
                'update',
                'thread',
                array(
                    '+:posts' => 1,
                    'lastuid' => $quick_uid,
                    'lastpid' => $quick_pid,
                    'last_date' => $myapp->data['time'],
                ),
                array(
                    'tid' => $thread_id,
                )

            );
            #记录我的发帖
            $myapp->DB(
                'insert',
                'mypost',
                array(
                    'uid' => $quick_uid,
                    'tid' => $thread_id,
                    'pid' => $quick_pid
                )
            );
            #设置返回数据
            $jsonResult = array(
                'result' => array(
                    'message' => $myapp->F('message_format', $quick_message, 1),
                    'pid' => $quick_pid,
                    'last_date' => $myapp->data['time'],
                    'page'=>ceil(($thread['posts']+1)/$GLOBALS['_XN']['settings']['post_size'])
                )
            );
            $myapp->json($jsonResult);    

        }
    }
    $myapp->json($_POST);
    */
    /*
    $myapp->json($errorPost);
}
#$attach_pids = [];
#取得帖子列表
#$postlist = $myapp->F('get_postlist',$thread,$page,$thread_order);
#if(empty($myapp->data['userlist'][$thread_uid])){
#    $myapp->data['userlist'][$thread_uid] = $myapp->DB('FetchRow','user',array('uid'=>$thread_uid));
#}
#$thread_user = $myapp->data['userlist'][$thread_uid];
$scriptThread = $myapp['router'][0].'-'.$thread_id;
$scriptForum = $myapp['router'][0].'-'.$forum_id;
$myapp->data['title']= $thread['subject'];
if(!empty($myapp->DB('Update','thread',array('+:views'=>1),array('tid'=>$thread_id)))){
    $thread['views'] += 1;
}
include $myapp->getTemplate($myapp->data['settings']['thread_template']);
*/
