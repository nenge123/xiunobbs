<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 主题,编辑主题,添加帖子,删除主题或者帖子
 */
defined('WEBROOT') or die('return to <a href="">Home</a>');
$tid = router_value(1);
$routername = 'thread';
if(is_numeric($tid)):
	$tid = intval($tid);
	$thread = $myapp->t('thread')->value($tid);
	if(!empty($thread)):
		$myapp->data['title'] = strip_tags($thread['subject']);
		$fid = intval($thread['fid']);
		$forum = $myapp->data['forumlist'][$fid];
		$forumhref = 'forum-'.$fid.'-';
		$forumindex = $forumhref.'1';
		$threadhref = 'thread-'.$thread['tid'].'-';
		$threadindex = $threadhref.'1';
		$threadact = array('asc','pid');
		$threadhashkey = 'post';
		#权限
		$access = $myapp->get_forum_access($fid);
		#权限修订
		if(!in_array($myapp->data['user']['gid'],array(1,2,3))):
			#非管理员 版主 超级版主
			#不是楼主本人,设置删除 编辑功能为假
			if($myapp->data['user']['uid'] != $thread['uid']):
				$access['allowdelete'] = false;
				$access['allowupdate'] = false;
			endif;
		endif;
		#插件改变权限
		$access = $myapp->plugin_set('thread_access',$access,$thread);
		if(empty($access['allowread'])):
			#没有阅读权限
			$myapp->data['msgtitle'] = $myapp->getLang('thread_unable_read');
			$myapp->data['msgcontent'] = $myapp->getLang('thread_unable_read_msg');
			include $myapp->template('msg/ajax');
			$myapp->exit();
		endif;
		if(router_value(2)=='aid'):
			#附件
			#跳转处理 非标准路由文件名,所以不受路由拦截影响
			include($myapp->data['path']['router'].'thread'.DIRECTORY_SEPARATOR.'attach.php');
		endif;
		if(router_value(2)=='post'):
			#附件
			#跳转处理 非标准路由文件名,所以不受路由拦截影响
			include($myapp->data['path']['router'].'thread'.DIRECTORY_SEPARATOR.'post.php');
		endif;
		router_set('fid',$fid);
		router_set('firstpid',intval($thread['firstpid']));
		router_set('tid',intval($thread['tid']));
		router_set('page',router_value(2,1)?:1);
		router_set('limit',settings_value('post_limit',5));
		$threadorder = router_value(3);
		if(!$threadorder||!in_array($threadorder,$threadact)):
			$threadorder = 'asc';
		endif;
		router_set('order',$threadorder);
		$threadlistorder = $threadindex.'-'.($threadorder==$threadact[0]?$threadact[1]:$threadact[0]);
		$threadorder = '-'.$threadorder;
		$myapp->data['posthash'] = $myapp->get_time_hash($threadhashkey);
		$uids = array();
		$pids = array();
		$userlist = array();
		if(!isset($_SERVER['HTTP_AJAX_FETCH'])):
			$authorpost = $myapp->t('post')->value($thread['firstpid']);
			$authorpost['message'] = safeHTML($authorpost['message']);
		endif;
		$postlist = $myapp->get_thread_postlist();
		if(!empty($postlist['list'])):
			$uids = array_column($postlist['list'],'uid');
			$pids = array_column($postlist['list'],'pid');
		endif;
		$uids[] = $thread['uid'];
		$pids[] = $thread['firstpid'];
		array_unique($uids);
		$userlist = $myapp->t('user')->safe_all($uids);
		$attachlist = $myapp->t('attach')->pids($pids);
		if(!empty($postlist['list'])):
			foreach($postlist['list'] as $pid=>$post):
				if(!empty($userlist[$post['uid']])):
					$postlist['list'][$pid]['username'] = $userlist[$post['uid']]['username'];
				endif;
				$postlist['list'][$pid]['create_date_fmt'] = $myapp->get_time_human($post['create_date']);
				$postlist['list'][$pid]['message_fmt'] = safeHTML(($post['message']));
				if(!empty($attachlist[$pid])):
					foreach($attachlist[$pid] as $attach):
						if($attach['isimage']):
							$postlist['list'][$pid]['imglist'][] = $attach;
						else:
							$postlist['list'][$pid]['filelist'][] = $attach;
						endif;
					endforeach;
				endif;
			endforeach;
		endif;
		if(!empty($authorpost)):
			if(isset($attachlist[$authorpost['pid']])):
				foreach($attachlist[$authorpost['pid']] as $attach):
					if($attach['isimage']):
						$authorpost['imglist'][] = $attach;
					else:
						$authorpost['filelist'][] = $attach;
					endif;
				endforeach;
			endif;
		endif;
		$thread['create_date_fmt'] = $myapp->get_time_human($thread['create_date']);
		$thread['username'] = $userlist[$thread['uid']]['username'];
		$thread['userposts'] = $userlist[$thread['uid']]['posts'];
		$thread['userthreads'] = $userlist[$thread['uid']]['threads'];
		$thread['lastposts'] = $thread['posts'] + 2;
		include $myapp->template('thread');
		$myapp->exit();
	else:	
		#帖子不存在
		$myapp->data['msgtitle'] = $myapp->getLang('thread_unknow');
		$myapp->data['msgcontent'] = $myapp->getLang('thread_unknow_message');
		include $myapp->template('msg/ajax');
		$myapp->exit();
	endif;
endif;