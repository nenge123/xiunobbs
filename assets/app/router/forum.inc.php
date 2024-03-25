<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 板块列表 板块帖子
 */
defined('WEBROOT') or die('return to <a href="">Home</a>');
$fid = router_value(1);
router_set('page',router_value(2,1)?:1);
router_set('limit',settings_value('forum_limit',30));
$forumact = array('desc','tid');
$forumorder = router_value(3);
if(!$forumorder||!in_array($forumorder,$forumact)):
    $forumorder = 'desc';
endif;
router_set('order',$forumorder);
if (!is_numeric($fid)||$fid==0) :
    goto showforum;
endif;
$fid = intval($fid);
if(empty($myapp->data['forumlist'][$fid])):
    goto noforum;
endif;
if (!in_array($fid, $myapp->get_forum_available())):
    goto noforum;
endif;
    $fid = intval($fid);
    router_set('fid',$fid);
    $myapp->data['title'] = $myapp->data['forumlist'][$fid]['name'];
    $forum = $myapp->data['forumlist'][$fid];
    $access = $myapp->get_forum_access($fid);
    #插件改变权限
    $access = $myapp->plugin_set('forum_access',$access,$fid);
    if(empty($access['allowread'])):
        $myapp->data['msgtitle'] = $myapp->getLang('forum_unable_read');
        $myapp->data['msgcontent'] = $myapp->getLang('forum_unable_read_message');
        include $myapp->template('forum/error');
        $myapp->exit();
    endif;
    if(isset($myapp->data['forumlist'][$fid]['orderby'])&&empty($myapp->data['router'][3])):
        $forumorder = $myapp->data['forumlist'][$fid]['orderby']?'tid':'desc';
        router_set('order',$forumorder);
    endif;
    $forumhref = 'forum-'.$forum['fid'].'-';
    $forumindex = $forumhref.'1';
    $forumhome = $forumhref.'1-';
    $threadorder = '-'.router_value('order');
    include $myapp->template('forum/index');
    $myapp->exit();
noforum:
    include $myapp->template('forum-no');
    exit;
showforum:
    $myapp->data['title'] = $myapp->getLang('forum_title');
    $forumhref = 'forum-0-';
    $forumindex = $forumhref.'1';
    $forumhome = $forumhref.'1-';
    $threadorder = '-'.router_value('order');
    include $myapp->template('forum/index');
    exit;