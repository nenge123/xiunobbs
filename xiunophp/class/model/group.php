<?php

namespace model;
use MyApp;
use MyDB;
/**
 * 用户组扩充函数
 */
class group
{
	/**
	 * 根据积分来调整用户组  
	 * 亦可用作解禁用户
	 * @param array|integer $user 用户信息
	 * @return boolean
	 */
	public static function update(array|int $user):bool
	{
		$grouplist = $GLOBALS['grouplist'];
		if(is_int($user)):
			$user = MyDB::t('user')->whereFirst(array('uid'=>$user));
		endif;
		#默认用户组
		$new_gid = $user['gid']<101?101:$user['gid'];
		// hook model_user_update_group_start.php
		// 遍历 credits 范围，调整用户组
		foreach ($grouplist as $group):
			if ($group['gid'] < 100):
				continue;
			endif; 
			$n = $user['posts'] + $user['threads']; // 根据发帖数
			// hook model_user_update_group_policy_start.php
			if ($n > $group['creditsfrom'] && $n < $group['creditsto']):
				if ($user['gid'] != $group['gid']):
					$new_gid = $group['gid'];
					break;
				endif;
			endif;
		endforeach;
		$result = MyDB::t('user')->update_by_where(array('gid'=>$new_gid),array('uid'=>$user['uid']));
		if(empty($result)):
			// hook model_user_update_group_end.php
			return FALSE;
		endif;
		return TRUE;
	}
}
