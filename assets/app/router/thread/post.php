<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 导肮 主题 分支操作 回帖
 * method POST
 * 请求地址 thread-{ID}-post.html
 */

defined('WEBROOT') or die('return to <a href="">Home</a>');
if(empty($thread)||empty($access['allowpost'])):
    #无权回帖 非法引用
    $msgtitle = 'post_unlogin';
    $msgcontent = 'post_unlogin_msg';
    goto showMsg;
endif;
if($myapp->data['method']=='POST'):
    $doctype = input_post('doctype',1);
    $quotepid =  input_post('quotepid',0);
    $message =  input_post('message',false);
    $posthash = input_post('hash',false);
    $posttime = intval(input_post('time',0));
    header('content-time:'.$myapp->data['time']);
    header('content-hash:'.$myapp->get_time_hash($threadhashkey));
    $mintime = settings_value('post_message_mintime',5);
    $maxtime = settings_value('post_message_maxtime',300);
    if($myapp->data['time'] - $posttime < $mintime):
        #操作过快
        $msgtitle = 'post_title';
        $myapp->data['msgcontent'] = $myapp->getLang('post_message_freq',array('num'=>$mintime + $posttime - $myapp->data['time']));
        goto showMsg;
    endif;
    if($myapp->data['time'] - $posttime > $maxtime ):
        #太久不操作 更新
        $msgtitle = 'post_title';
        $myapp->data['msgcontent'] = $myapp->getLang('post_invalid_time',array('num'=>$mintime));
        goto showMsg;
    endif;
    if($myapp->get_time_decode($posthash,$posttime)!=$threadhashkey):
        #验证失败
        $msgtitle = 'post_title';
        $myapp->data['msgcontent'] = $myapp->getLang('post_invalid_hash',array('num'=>$posttime - $myapp->data['time']));
        goto showMsg;
    endif;
    $size = settings_value('post_message_size',15);
    if(empty($message)||mb_strlen($message)<$size):
        #回帖内容 太短
        $msgtitle = 'post_title';
        $myapp->data['msgcontent'] = $myapp->getLang('post_empty_message',array('size'=>$size));
        goto showMsg;
    endif;
    $maxsize = settings_value('post_message_maxsize',0);
    if($maxsize>0&&strlen($message)>$maxsize):
        #回帖内容 太长
        $msgtitle = 'post_title';
        $myapp->data['msgcontent'] = $myapp->getLang('post_message_longest',array('maxsize'=>$maxsize,'size'=>strlen($message)));
        goto showMsg;
    endif;
    #insert2json
    $newpid = $myapp->t('post')->insert2json(array(
        'tid'=>$thread['tid'],
        'uid'=>$myapp->data['user']['uid'],
        'create_date'=>$myapp->data['time'],
        'userip'=>ip2str($myapp->data['ip']),
        'isfirst'=>0,
        'doctype'=>intval($doctype),
        'quotepid'=>intval($quotepid),
        'message'=>$message,
        'message_fmt'=>'',
    ));
    $myapp->t('thread')->update2json(
        array(
            'posts'=>$thread['posts']+1,
            'lastpid'=>$newpid,
            'last_date'=>$myapp->data['time']
        ),
        'tid',
        $thread['tid']
    );
    #标记发帖成功
    #用户端AJAX检测到HEADER 删除用户的回帖内容
    header('content-post:'.$newpid);
    $myapp->data['msgtitle'] = $myapp->getLang('post_success');
    $myapp->data['msgcontent'] = $myapp->getLang('post_success_msg',array('maxsize'=>$maxsize,'size'=>strlen($message)));
    $myapp['ajax-nav'] = array(
        array(
            'name'=> $myapp->getLang('post_refresh_view'),
            'href'=> $threadindex.'-'.$threadact[1],
        )
    );
    goto showMsg;
endif;
#非法请求 本页面只允许POST请求,GET请求错误信息
$msgtitle = 'post_invalid_request';
$msgcontent = 'post_invalid_request_msg';
goto showMsg;
showMsg:
if(isset($msgtitle))$myapp->data['msgtitle'] = $myapp->getLang($msgtitle);
if(isset($msgcontent))$$myapp->data['msgcontent'] = $myapp->getLang($msgcontent);
include $myapp->template('msg/ajax');
$myapp->exit();