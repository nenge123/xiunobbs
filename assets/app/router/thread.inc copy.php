<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 主题,编辑主题,添加帖子,删除主题或者帖子
 */
defined('WEBROOT') or die('return to <a href="">Home</a>');
$ajax_template = $myapp->HEAD('Ajax-Template');
if ($ajax_template):    
	if(in_array($ajax_template,array('comment','delete','reply','delcomment'))):
        #特定模板
        include $myapp->template('thread/ajax-'.$ajax_template);
    endif;
    exit;
endif;
$tid = router_value(1,'newpost');
#参数2不存在 或者为 newpost 视为发帖页面 thread-newpost
if($tid=='newpost'):
    $tid = 0;
    if($myapp->data['router'][2]>0):
        #存在参数3 视为某板块发帖
        $myapp->data['fid'] = intval($myapp->data['router'][2]);
        #跳转权限验证 认证
        goto get_access;
        exit;
    else:
        #跳转 可视板块列表
        include $myapp->get_router_link('forum');
        exit;
    endif;
#参数2存在 且为数字 主题页面
elseif(is_numeric($tid)):
    #查询主题
    $tid = intval($tid);
    $thread = $myapp->t('thread')->value($tid);
    #没找到帖子
    if (empty($thread)):
        goto thread_not_found;
    endif;
endif;
#获取主题成功 触发插件方法 router_thread_load

#设定当前主题ID
$myapp->data['fid'] = $thread['fid'];
#权限管理
get_access:
$forum = $myapp->get_forum($myapp->data['fid']);
if(empty($forum)):
    #无效板块
    goto forum_not_found;
endif;
$thread_access = $myapp->get_access($myapp->data['fid']);
$moduids = empty($forum['moduids'])?array():$forum['moduids'];
$userdata = $myapp->data['user'];
$allow_read = $thread_access['allowread'];
$allow_post = $thread_access['allowpost'];
$allow_down = $thread_access['allowdown'];
$allow_attach = $thread_access['allowattach'];
#再次编辑
$allow_edit = $thread_access['allowupdate'];
#删除
$allow_delete = $thread_access['allowdelete'];
#删除帖内留言
$allow_delcomment= $thread_access['allowpost'];
#添加帖内留言
$allow_addcomment= $thread_access['allowpost'];
#ip
$allow_viewip = $thread_access['allowviewip'];
#禁用置顶主题
$allow_top = false;
#禁用移动主题
$allow_move = false;

$is_admin = false;
#匿名化
$allow_hiddenuser = false;
if(!empty($thread_access['hiddenusername'])):
    $allow_hiddenuser = true;
endif;
#锁帖 通过附加 islock锁帖
if(!empty($thread['islock'])):
    $allow_post = false;
    $allow_attach = false;
    $allow_edit = false;
    $allow_delete = false;
    $allow_delcomment= false;
endif;
if (in_array($userdata['gid'], array(1, 2)) || ($userdata['gid']==3&&in_array($userdata['uid'], $moduids))):
    $allow_read = true;
    $allow_post = true;
    $allow_down = true;
    $allow_attach = true;
    $allow_edit = true;
    $allow_delete = true;
    $allow_delcomment= true;
    $allow_viewip = true;
    $is_admin = true;
    #置顶主题
    $allow_top = $thread_access['allowtop'];
    #移动主题
    $allow_move = $thread_access['allowmove'];
endif;
#配置权限 触发插件方法 router_thread_allow

if(empty($thread)):
    #此处为板块新帖 权限认证后处理
    if($allow_post):
        include $myapp->get_router_link('api/thread/edit-newpost');
        exit;
    else:
        #无权发帖 
        goto unable_new_thread;
        exit;
    endif;
endif;
#初始化主题数据 thread-tid-xxx
#添加tid记录
$myapp->data['tid'] = $thread['tid'];
#配置权限 触发插件方法 router_thread_read
$download_id = intval($myapp->HEAD('download-id'));
$delete_post_id = intval($myapp->HEAD('delete-post'));
$delete_comment_id = intval($myapp->HEAD('delete-comments'));
$myapp->data['title'] = $thread['subject'];
$postlist = array();
$attachlist = array();
$post_query_list = array();
$thread_where = array();
$query_uids = array($thread['uid']);
$only = 0;
$order = array();
#页数初始化
$page = intval($myapp->data['router'][2]);
if(!$page)$page=1;
#基础路由链接
$link_thread = $myapp->data['router'][0].'-'.$thread['tid'];
$link_page = $link_thread.'-'.$page;
$link_edit = $link_thread.'-edit';
$link_reply = $link_thread.'-reply';
$link_newpost = $myapp->data['router'][0].'-newpost-'.$thread['fid'];
$order_type = strtoupper($myapp->data['router'][3]) == 'DESC' ? 'DESC': 'ASC';
$thread_limit = empty($myapp->data['settings']['post_size'])?40:$myapp->data['settings']['post_size'];
#存在只看某某
if(isset($_GET['only'])&&is_numeric($_GET['only'])):
    #$thread_where['!:isfirst'] = 1;
    $thread_where['uid'] = intval($_GET['only']);
endif;
#首页查询处理
if($page==1):
    #简化查询 首页增加查询数量 把楼主加进去
    $order = array(
        'isfirst'=>'DESC'
    );
    $thread_limit+=1;
else:
    #非首页 过滤楼主
    $thread_where['!:isfirst'] = 1;
endif;
$order['create_date'] = $order_type;
$thread_where['tid'] = $thread['tid'];

if(!empty($_POST)):
    #发现post请求
    if(!empty($_POST['comments'])):
        #关键请求 点评帖子
        if($allow_addcomment):
            include $myapp->get_router_link('api/thread/insert-comment');
        else:
            goto unable_addcomments;
        endif;
        exit;
    endif;
    if(!empty($_POST['message'])):
        #回帖请求
        if($allow_post):
            if($page==1):
                unset($order['isfirst']);
                $thread_limit-=1;
            endif;
            $no_error_return = true;
            include $myapp->get_router_link('api/thread/insert-post');
            goto show_thread;
        else:
            goto unable_addpost;
        endif;
        exit;
    endif;
    #后续POST处理
    
endif;
if (!$allow_read):
    #权限不足 跳转无权访问
    goto unable_visit_thread;
    exit;
endif;


if ($download_id>0):
    #处理下载请求 此请求只能AJAX 无法通过常规URL方式下载😄
    if($allow_down):
        include $myapp->get_router_link('api/thread/download-attach');
    else:
        #无权下载
        
        $myapp->error('attach_unable_forum_download','attach_unable_download');
    endif;
    exit;
endif;
if ($delete_post_id>0):
    #删除帖子  此请求只能AJAX 无法通过常规URL方式下载😄
    if($allow_delete):
        $postid = $delete_post_id;
        include $myapp->get_router_link('api/thread/delete-post');
    else:
        #无权删除
        goto unable_delete;
    endif;
    exit;
endif;
if ($delete_comment_id>0):
    #删除留言  此请求只能AJAX 无法通过常规URL方式下载😄
    if($allow_delcomment):
        $cid = $delete_comment_id;
        include $myapp->get_router_link('api/thread/delete-comment');
    else:
        #无权删除
        goto unable_delete;
    endif;
    exit;
endif;
if(!is_numeric($myapp->data['router'][2])):
    if($myapp->data['router'][2]=='edit'):
        #编辑帖子 thread-tid-[edit]-[pid]
        $subject = $thread['subject'];
        $edit_mode = 'markdown';
        if(is_numeric($myapp->data['router'][3])&&$myapp->data['router'][3]>0):
            $post = $myapp->t('post')->fetch(array('pid'=>intval($myapp->data['router'][3]),'tid'=>$thread['tid']));
            if(empty($post)):
                goto unable_edit_post;
                exit;
            endif;
        endif;
        if($allow_edit):
            #if(is_numeric($myapp->data['router'][3])):
            #thread-tid-edit-pid
            $myapp->data['header_js_module'][] = 'edit';
            include $myapp->template('thread/edit-post');
        else:
            #无权编辑
            goto unable_edit;
        endif;
        exit;
    endif;
    $myapp->data['router'][2] = 1;
endif;

show_thread:
    
    if(empty($post_query_list)):
        $post_query_list = $myapp->t('post')->all(
            $thread_where,
            array(
                'order'=>$order,
                'limit'=>array(($page-1)*$thread_limit,$thread_limit
            )
        ));
        if($page>1&&empty($myapp->data['ajax'])):
            #非首页
            $firstpost = $myapp->t('post')->fetch(array(
                'tid'=>$thread['tid'],
                'isfirst'=>1
            ));
            $post_query_list[$firstpost['pid']] = $firstpost;
        endif;
        
    endif;
    $query_uids += array_column($post_query_list,'uid');
    $attach_ids = array();
    foreach($post_query_list as $post_pid=>$post_data):
        if(!empty($post_data['images'])||$post_data['files']>0):
            $attach_ids[] = $post_pid;
        endif;
    endforeach;
    #获取附件ID
    
    
    if(empty($attachlist)):
        if($allow_down&&!empty($attach_ids)):
            #允许下载文件
            $attachlist = $myapp->t('attach')->pids($attach_ids);
            
        endif;
    endif;
    #获取楼中楼
    $pids = array_column($post_query_list,'pid');
    $comments = $myapp->t('comments')->pids($pids);
    if(!empty($comments)){
        $query_uids += array_column($post_query_list,'uid');
    }
    #引用查询
    $quotepids = array_filter(array_column($post_query_list,'quotepid'));
    $quotepids = array_filter($quotepids,fn($value)=>$value&&!in_array($value,$quotepids));
    if(!empty($quotepids)):
        $quote_list = $myapp->t('post')->all(array(
            'pid'=>$quotepids,
            'tid'=>$thread['tid']
        ));
        if(!empty($quote_list)):
            $query_uids = array_column($quote_list,'uid');
        endif;
    endif;
    #查询需要查询的用户
    $query_uids = array_filter($query_uids);
    $userlist = $myapp->t('user')->uids($query_uids);
    if ($order_type == 'ASC'):
        $floor = ($page - 1) * $thread_limit + 1;
    else:
        $floor = $thread['posts'] + 2;
    endif;
    if(isset($userlist[$thread['uid']])):
        $thread['username'] = $userlist[$thread['uid']]['username'];
        $thread['gid'] = $userlist[$thread['uid']]['gid'];
        $thread['user'] = $userlist[$thread['uid']];
    else:
        $thread['gid'] = 7;
        $thread['uid'] = 0;
        $thread['username'] = $language['user_name_delete'];
    endif;
    foreach($post_query_list as $pid=>$post):
        if(!empty($post['uid'])):
            if(isset($userlist[$post['uid']])):
                $post['username'] = empty($post['username'])?$userlist[$post['uid']]['username']:$post['username'];
                $post['gid'] = $userlist[$post['uid']]['gid'];
                $post['user'] = $userlist[$post['uid']];
            else:
                $post['gid'] = 7;
                $post['uid'] = -1;
                $post['username'] = empty($post['username'])?$language['user_name_delete']:$post['username'];
            endif;
        else:
            $post['username'] = empty($post['username'])?$language['user_name_unknow']:$post['username'];
        endif;
        if(isset($attachlist[$post['pid']])):
            $post['attachlist'] = $attachlist[$post['pid']];
        endif;
        $post = $myapp->formatPost($post);
        if(isset($post['quotepid'])&&isset($quotelist[$post['quotepid']])):
            foreach($quotelist[$post['quotepid']] as $quotepost):
                if(!empty($quotepost['uid'])):
                    if(isset($userlist[$quotepost['uid']])):
                        $quotepost['username'] = empty($quotepost['username'])?$userlist[$quotepost['uid']]['username']:$quotepost['username'];
                        $quotepost['gid'] = $userlist[$quotepost['uid']]['gid'];
                        $quotepost['user'] = $userlist[$quotepost['uid']];
                    else:
                        $quotepost['gid'] = 7;
                        $quotepost['uid'] = -1;
                        $quotepost['username'] = empty($quotepost['username'])?$language['user_name_delete']:$quotepost['username'];
                    endif;
                else:
                    $quotepost['username'] = empty($quotepost['username'])?$language['user_name_unknow']:$quotepost['username'];
                endif;
                $quotepost = $myapp->formatPost($quotepost);
                $post['quotelist'][$quotepost['pid']] = $quotepost;
            endforeach;
        endif;
        if(!empty($comments[$pid])):
            $post['comments'] = $comments[$pid];
        endif;
        if($post['isfirst']):
            unset($post['user']);
            $thread['pid'] = $post['pid'];
            $thread['message'] = $post['message'];
            $thread['post'] = $post;
        else:
            if($order_type=='ASC'):
                $floor+=1;
            else:
                $floor-=1;
            endif;
            $post['floor'] = $floor;
            $postlist[$post['pid']] = $post;
        endif;
    endforeach;
    //print_r($post_query_list);
    unset($attachlist,$post_query_list,$userlist,$query_uids,$pids,$attach_ids);
    #print_r($postlist);
    #exit;
        
    $myapp->data['header_js_module'][] = 'thread';
    if($myapp->data['ajax']=='ajax'):
        include $myapp->template('thread/replay-post');
        exit;
    endif;
    include $myapp->template('thread');
    exit;

forum_not_found:
    #主题不存在
    
    $myapp->error('forum_unable_found_fid','forum_unable_fid');
    exit;
thread_not_found:
    #主题不存在
    $myapp->error('thread_unable_found_tid','thread_unable_tid');
    exit;
unable_visit_thread:
    #非开放板块
    
    $myapp->error('thread_unable_visit_tid','thread_unable_visit');
    exit;
unable_edit_post:
    #编辑帖子失败
    
    $myapp->error('post_unable_found_pid','post_unable_pid');
    exit;
unable_edit:
    #无法编辑
    
    $myapp->error('thread_unable_edit_pid','thread_unable_edit');
    exit;
unable_delete:
    #无法编辑
    
    $myapp->error('thread_unable_delete_pid','thread_unable_delete');
    exit;
unable_addpost:
    #板块不允许发帖
    
    $myapp->error('thread_unable_new_post','thread_unable_post');
    exit;

unable_addcomments:
    #板块不允许发帖
    
    $myapp->error('thread_unable_new_comments','thread_unable_comments');
    exit;
unable_new_thread:
    #板块不允许发帖
    
    $myapp->error('thread_unable_new_thread','thread_unable_thread');
    exit;