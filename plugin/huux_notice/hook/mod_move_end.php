<?php exit;
    // 消息(主题-移动) 重写foreach问题不大, 后期如果程序升级这里可作调整
	foreach($threadlist as &$thread) {
		$fid = $thread['fid'];
		$tid = $thread['tid'];
		if(forum_access_mod($fid, $gid, 'allowmove')) {
			if($fid == $newfid) continue;			
			// notice send
			$newforum = forum_read($fid);
		   	$thread['subject'] = huux_notice::notice_substr($thread['subject'], 20); 
			$todo = lang('notice_template_yourtopic_move');
			$thread_move_notice_message = lang('notice_admin').'<span class="handle mx-1">'.$todo.'</span>'.lang('notice_template_yourtopic').'<a href="'.url("thread-$thread[tid]").'">《'.$thread['subject'].'》</a>'.lang('notice_template_yourtopic_moveto').' <a href="'.url("forum-$newforum[fid]").'">【'.$newforum['name'].'】</a>';
			$notice_nid = huux_notice::notice_send($user['uid'], $thread['uid'], $thread_move_notice_message, 3);
			// end notice send
		}
	}

?>