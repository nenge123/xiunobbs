<?php
defined('XIUNO')||die('return to <a href="">Home</a>');
if(!empty( $myapp->data['forumlist'][$router_value]['name'])){ 
    $myapp->data['title'] = $myapp->data['forumlist'][$router_value]['name'];
}
include $myapp->template('forum');
/*
$forum_id = intval($myapp->router->act);
$forum_id = $forum_id ?:1;
if(empty($myapp->data['forumlist'][$forum_id])||!empty($myapp['forumlist'][$forum_id]['display'])){
    $myapp->exit($language['forum_not_exists']);
}
#用户gid
$gid = empty($myapp->data['user']['gid'])?0:$myapp->data['user']['gid'];
#设置当前板块信息
$forum = $myapp['forumlist'][$forum_id];
#权限
$forum_access = $myapp->data['forumlist'][$forum_id]['access'];
if(empty($forum_access['allowread'])){
    $myapp->exit($language['insufficient_visit_forum_privilege']);
}
#设置默认页数
if(!empty($myapp['router'][0])){
    $page = intval($myapp['router'][0]);
}
$page = empty($page)?$page:1;
#是否有权限编辑帖子
$allowEditThread = false;
if($gid&&(in_array($gid,array(1,2))||($gid==3&&!empty($forum['modlist'][$myapp->data['user']['uid']])))){
    $allowEditThread = true;
}
if($myapp->data['method']=='POST'){
    $errorPost = array(
        'title'=>$language['unknow_action'],
        'html'=>$language['unknow_action_txt'],
        'hidden'=>array(
            "footer"=>true
        )
    );
    if($allowEditThread&&!empty($_POST['action'])&&!empty($_POST['tids'])){
        $tids = explode(',',$_POST['tids']);
        $newtids = [];
        foreach($tids as $k=>$v){
            $v = intval($v);
            if($v){
                $newtids[]=$v;
            }
        }
        if(!empty($tids)){
            if(in_array($_POST['action'],array('1','2','3','4','5','6','7','8'))){
                $reloadLink = "";
                if($_POST['action']=='1')$threadResult = $myapp->DB('Update','thread',array('top'=>3),array('tid'=>$newtids));
                if($_POST['action']=='2')$threadResult = $myapp->DB('Update','thread',array('top'=>1),array('tid'=>$newtids));
                if($_POST['action']=='3')$threadResult = $myapp->DB('Update','thread',array('top'=>0),array('tid'=>$newtids));
                if($_POST['action']=='8')$threadResult = $myapp->DB('Update','thread',array('top'=>2),array('tid'=>$newtids));
                if($_POST['action']=='7')$threadResult = $myapp->DB('Update','thread',array('top'=>-1),array('tid'=>$newtids));
                if($_POST['action']=='4')$threadResult = $myapp->DB('Update','thread',array('closed'=>1),array('tid'=>$newtids));
                if($_POST['action']=='5')$threadResult = $myapp->DB('Update','thread',array('closed'=>0),array('tid'=>$newtids));
                if($_POST['action']=='6'){
                    if(!empty($_POST['forumid'])&&!empty($myapp['forumlist'][$_POST['forumid']])){
                        $postfid = intval($_POST['forumid']);
                        $threadResult = $myapp->DB('Update','thread',array('fid'=>$postfid),array('tid'=>$newtids));
                        $reloadLink = $myapp->url('forum-'.$postfid);
                    }else{
                        $myapp->json($errorPost);
                    }
                }
                if(!empty($threadResult['line'])){
                    if(in_array($_POST['action'],array('1','2','3','7','8'))){
                        $myapp->getCache('threadtop',true);
                    }
                    $myapp->json(array(
                        'title'=>$language['update_successfully'],
                        'html'=>$language['update_thread_num'].$threadResult['line'],
                        'hidden'=>array(
                            "footer"=>true
                        ),
                        'href'=>$reloadLink,
                        'success'=>true
                    ));
                }else{
                    $myapp->json(array(
                        'title'=>$language['update_failed'],
                        'html'=>$language['update_nothings'],
                        'hidden'=>array(
                            "footer"=>true
                        )
                    ));
                }
            }else if($_POST['action']=='9'){
                $threadResult = $myapp->DB('Delete','thread',array('tid'=>$newtids));
                $forumUpate = array();
                $postResult = $myapp->DB('Delete','post',array('tid'=>$newtids));
                if(!empty($threadResult['line'])){
                    $forumUpate['-:threads'] = $threadResult['line'];
                }
                if(!empty($forumUpate)){
                    $myapp->DB('Update','forum',$forumUpate,array('fid'=>$forum_id));
                    $myapp->json(array(
                        'title'=>$language['delete_successfully'],
                        'html'=>$language['delete_thread_num'].(!empty($forumUpate['-:threads'])?$forumUpate['-:threads']:0).'<hr>'.$language['delete_post_num'].(!empty($postResult['line'])?$postResult['line']:0).'<hr>',
                        'hidden'=>array(
                            "footer"=>true
                        ),
                        'success'=>true
                    ));
                }else{
                    $myapp->json(array(
                        'title'=>$language['delete_failed'],
                        'html'=>$language['delete_nothings'],
                        'hidden'=>array(
                            "footer"=>true
                        )
                    ));

                }
            }
        }

    }
    $myapp->json($errorPost);
}
#重置缓存记录中的数量
$forum['threads'] = 0;
if(!empty($myapp['settings']['forum_info'])){
    #实时显示总主题数,今日发帖,今日主题
    $nowforum = $myapp->DB('FetchRow','forum',array('fid'=>$forum_id));
    /*
    $forum['threads'] = $nowforum['threads'];
    $forum['todayposts'] = $nowforum['todayposts'];
    $forum['todaythreads'] = $nowforum['todaythreads'];
    */
    /*
    $forum = array_merge($forum,$nowforum);
}
#设置默认 查询排序
$threadOrder = [];
if(!empty($myapp->router->data[3])){
    $scriptOrder = $myapp->router->data[3] == 'new'?'create_date':'last_date';
}else{
    $scriptOrder = $myapp['settings']['thread_order'];
}
$threadOrder[$scriptOrder] = $scriptOrder=='last_date'?'DESC':'ASC';
$myapp->data['title']= $forum['name'];

$myapp->getCache('threadtop');
include $myapp->getTemplate($myapp->data['settings']['forum_template']);
*/
?>