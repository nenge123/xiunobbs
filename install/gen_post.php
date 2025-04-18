<?php

#exit; // 如果要使用请注释掉该行

// 跳过路由
define('SKIP_ROUTE', TRUE);

include '../index.php';

$fid = 2;	// 版块 id
$uid = 1;	// 用户 id
$gid = 1;	// 用户组 id; 1: 管理员; 101:普通用户
for($i=1; $i<100; $i++) {
	$subject = '欢迎使用 Xiuno BBS 4.0 新一代论坛系统。'.$i;
	$message = '祝您使用愉快！';
	$thread = array(
		'fid'=>$fid,
		'uid'=>$uid,
		'subject'=>$subject,
		'doctype'=>0,
		'message'=>$message,
		'time'=>$_SERVER['REQUEST_TIME'],
		'longip'=>$longip,
	);
	$tid = thread_create($thread, $firstpid);
	for($j=0; $j<10; $j++) {
		$post = array(
			'tid'=>$tid,
			'uid'=>$uid,
			'create_date'=>$_SERVER['REQUEST_TIME'],
			'userip'=>$longip,
			'isfirst'=>0,
			'doctype'=>0,
			'message'=>$message.$j,
		);
		$pid = post_create($post, $fid, $gid);
	}
	if($i % 100 == 0) echo '.';
}

echo '生成数据完毕';
