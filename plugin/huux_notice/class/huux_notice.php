<?php
!defined('APP_PATH') AND exit('Access Denied.');
use model\user;
/**
 * 短信息
 */
class huux_notice
{
	use \script\plugunbase;
	// ------------> 最原生的 CURD，无关联其他数据。

	/** 创建消息 */
	public static function dbcreate($arr)
	{

		$r = db_create('notice', $arr);
		return $r;
	}

	/** 更新消息 */
	public static function dbupdate($nid, $arr)
	{

		$r = db_update('notice', array('nid' => $nid), $arr);
		return $r;
	}

	/** 读取消息 */
	public static function notice__read($nid)
	{

		$post = db_find_one('notice', array('nid' => $nid));
		return $post;
	}

	/** 删除消息 */
	public static function dbdelete($nid)
	{

		$r = db_delete('notice', array('nid' => $nid));
		return $r;
	}


	/** 查找消息 */
	public static function dbfind($cond = array(), $orderby = array(), $page = 1, $pagesize = 20)
	{

		$noticelist = db_find('notice', $cond, $orderby, $page, $pagesize, 'nid');
		return $noticelist;
	}

	// ------------> 关联 CURD

	/** 发送信息 */
	public static function notice_send($fromuid, $recvuid, $message, $type = 99)
	{
		if (empty($fromuid) || empty($recvuid)) return FALSE;
		if ($fromuid == $recvuid) return FALSE;
		$type == 0 and $type = 99;

		$arr = array(
			'fromuid' => $fromuid,
			'recvuid' => $recvuid,
			'create_date' => $_SERVER['REQUEST_TIME'],
			'isread' => 0,
			'type' => $type,        //0:全部 1:通知 2:评论 3:主题 
			'message' => $message,
		);

		//notice_message_fmt($arr, $gid);

		$nid = self::dbcreate($arr);
		if ($nid === FALSE) return FALSE;

		/** 更新统计数据 */
		user__update($recvuid, array('unread_notices+' => 1, 'notices+' => 1));

		return $nid;
	}

	/** 查找用户的消息 */
	public static function notice_find_by_recvuid($recvuid, $page = 1, $pagesize = 20, $type = 99)
	{

		$cond = array('recvuid' => $recvuid, 'type' => $type);
		$type == 0 and $cond = array('recvuid' => $recvuid);

		$noticelist = self::notice_find($cond, $page, $pagesize);

		return $noticelist;
	}

	/** 更新(用户)所有消息为(已读) */
	public static function notice_update_by_recvuid($recvuid, $arr = array('isread' => 1))
	{

		$r = db_update('notice', array('recvuid' => $recvuid), $arr);
		if ($r === FALSE) return FALSE;

		// 更新统计数据	
		user__update($recvuid, array('unread_notices' => 0));

		return $r;
	}

	/** 更新单条消息为(已读) */
	public static function notice_update($nid, $arr = array('isread' => 1))
	{

		$notice = self::notice__read($nid);
		if (empty($notice)) return FALSE;

		$recvuid = $notice['recvuid'];

		$r = self::dbupdate($nid, $arr);
		if ($r === FALSE) return FALSE;

		// 更新统计数据	
		user__update($recvuid, array('unread_notices-' => 1));

		return $r;
	}

	/** 删除单条消息 */
	public static function notice_delete($nid)
	{

		$notice = self::notice__read($nid);
		if (empty($notice)) return TRUE;

		$recvuid = $notice['recvuid'];
		$isread = $notice['isread'];

		$r = self::dbdelete($nid);
		if ($r === FALSE) return FALSE;

		// 更新统计数据	
		user__update($recvuid, array('notices-' => 1));

		// 如果信息是未读状态，用户未读-1
		$isread == 0 and user__update($recvuid, array('unread_notices-' => 1));


		return $r;
	}

	/** 删除用户所有消息 */
	public static function dbclear($recvuid)
	{

		$r = db_delete('notice', array('recvuid' => $recvuid));
		if ($r === FALSE) return FALSE;

		// 更新统计数据	
		user__update($recvuid, array('unread_notices' => 0, 'notices' => 0));

		return $r;
	}

	/** 获取消息信息(含有用户名和头像)的方法 */
	public static function notice_find($cond = array(), $page = 1, $pagesize = 20)
	{

		$noticelist = self::dbfind($cond, array('nid' => -1), $page, $pagesize);

		if ($noticelist) foreach ($noticelist as &$notice) self::format($notice);

		return $noticelist;
	}

	public static function notice_find_by_nids($nids, $order = array('nid' => -1))
	{
		if (!$nids) return array();
		$noticelist = db_find('notice', array('nid' => $nids), $order, 1, 1000, 'nid');
		if ($noticelist) foreach ($noticelist as &$notice) self::format($notice);

		return $noticelist;
	}

	/** 获取消息前格式化信息 */
	public static function format(&$notice)
	{

		if (empty($notice)) return;
		$notice_menu = self::notice_menu();
		$notice['create_date_fmt'] = humandate($notice['create_date']); //友好的时间
		$fromuser = user_read_cache($notice['fromuid']);
		$recvuser = user_read_cache($notice['recvuid']);

		$notice['from_username'] = $fromuser['username'];
		$notice['from_user_avatar_url'] = $fromuser['avatar_url'];
		//$notice['from_user'] = $fromuser;// 暂时不用，以后需要再说

		$notice['recv_username'] = $recvuser['username'];
		$notice['recv_user_avatar_url'] = $recvuser['avatar_url'];

		!isset($notice_menu[$notice['type']]) and $notice['type'] = 99;

		$notice['name'] = isset($notice_menu[$notice['type']]['name']) ? $notice_menu[$notice['type']]['name'] : 'message';
		$notice['class'] = isset($notice_menu[$notice['type']]['class']) ? $notice_menu[$notice['type']]['class'] : 'info';
		$notice['icon'] = isset($notice_menu[$notice['type']]['icon']) ? $notice_menu[$notice['type']]['icon'] : '';
	}

	// ------------> 其他方法

	/** 发送时格式化关联用户组，暂未使用 */
	public static function notice_message_fmt(&$arr, $gid)
	{

		// 截取255字节，管理员发送的信息不截取
		//非管理员屏蔽HTML
		$arr['message'] = ($gid == 1 ? $arr['message'] : mb_substr(lib\html::getText($arr['message']), 0, 255));
	}

	/** 消息截取 */
	public static function notice_substr($s, $len = 20, $htmlspe = TRUE)
	{

		if ($htmlspe == FALSE) {
			$s = strip_tags($s);
			$s = htmlspecialchars($s);
		}
		$more = xn_strlen($s) > $len ? '...' : '';
		$s = xn_substr($s, 0, $len) . $more;

		return $s;
	}

	/** 统计消息列表数量 */
	public static function notice_count($cond = array())
	{

		$n = db_count('notice', $cond);

		return $n;
	}
	public static function table()
	{
		return MyDB::t('notice');
	}
	public static function notice_menu()
	{
		$notice_menu = array(
			0 => array(
				'url' => url('my-notice'),
				'name' => MyApp::Lang('notice_lang_all'),
				'class' => 'info',
				'icon' => ''
			),
			2 => array(
				'url' => url('my-notice-2'),
				'name' => MyApp::Lang('notice_lang_comment'),
				'class' => 'primary',
				'icon' => ''
			),
			3 => array(
				'url' => url('my-notice-3'),
				'name' => MyApp::Lang('notice_lang_system'),
				'class' => 'danger',
				'icon' => ''
			),
			// hook notice_route_menu_array_end.php
			99 => array(
				'url' => url('my-notice-99'),
				'name' => MyApp::Lang('notice_lang_other'),
				'class' => 'success',
				'icon' => 'bell'
			)
		);
		// hook notice_route_menu_return_before.php
		return $notice_menu;
	}
	/**
	 * 获取消息数量
	 */
	public static function count(array $where): int
	{
		return self::table()->whereCount($where);
	}
	public static function list(array $where, array $order = array(), int $page = 1, int $pagesize = 20): array
	{
		$list = self::table()->whereAll($where, MyDB::ORDER($order) . MyDB::LIMIT($page, $pagesize));
		return self::format_list($list);
	}
	public static function format_list($list):array
	{
		
		$uids = array_merge(array_column($list, 'fromuid'), array_column($list, 'recvuid'));
		$uids = array_unique($uids);
		$userlist = MyDB::t('user')->whereAll(array('uid' => $uids), '', array('uid', 'username', 'avatar'));
		$userlist = array_column($userlist, null, 'uid');
		$notice_menu = self::notice_menu();
		foreach ($list as $k => $notice):
			$list[$k] += array(
				'create_date_fmt'=>humandate($notice['create_date']), //友好的时间
				'from_username'=>MyApp::Lang('guest'),
				'from_url'=>'#',
				'from_user_avatar_url'=>user::avatar(),
				'recv_username'=>MyApp::Lang('guest'),
				'recv_url'=>'#',
				'recv_user_avatar_url'=>user::avatar(),
			);
			if (isset($userlist[$notice['fromuid']])):
				$list[$k]['from_username'] = $userlist[$notice['fromuid']]['username'];
				$list[$k]['from_url'] = MyApp::topurl('user-'.$notice['fromuid']);
				$list[$k]['from_user_avatar_url'] = user::avatar($userlist[$notice['fromuid']]);
			endif;
			if (isset($userlist[$notice['recvuid']])):
				$list[$k]['recv_username'] = $userlist[$notice['recvuid']]['username'];
				$list[$k]['recv_url'] = MyApp::topurl('user-'.$notice['recvuid']);
				$list[$k]['recv_user_avatar_url'] = user::avatar($userlist[$notice['recvuid']]);
			endif;
			!isset($notice_menu[$notice['type']]) and $notice['type'] = 99;
			$list[$k]['name'] = isset($notice_menu[$notice['type']]['name']) ? $notice_menu[$notice['type']]['name'] : 'message';
			$list[$k]['class'] = isset($notice_menu[$notice['type']]['class']) ? $notice_menu[$notice['type']]['class'] : 'info';
			$list[$k]['icon'] = isset($notice_menu[$notice['type']]['icon']) ? $notice_menu[$notice['type']]['icon'] : '';
		endforeach;
		return $list;
	}
}
huux_notice::$dir = basename(dirname(__DIR__));
