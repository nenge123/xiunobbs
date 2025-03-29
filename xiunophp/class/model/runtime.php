<?php

namespace model;
use MyApp;
class runtime
{
	public static array $runtime;
	// hook model_runtime_start.php
	/**
	 * 论坛统计初始化
	 */
	public static function init()
	{
		// hook model_runtime_init_start.php
		self::$runtime = cache_get('runtime'); // 实时运行的数据，初始化！
		if (self::$runtime === NULL || !isset(self::$runtime['users'])) {
			self::$runtime = array();
			self::$runtime['users'] = user_count();
			self::$runtime['posts'] = post_count();
			self::$runtime['threads'] = thread_count();
			self::$runtime['posts'] -= self::$runtime['threads']; // 减去首帖
			self::$runtime['todayusers'] = 0;
			self::$runtime['todayposts'] = 0;
			self::$runtime['todaythreads'] = 0;
			self::$runtime['onlines'] = max(1, online_count());
			self::$runtime['cron_1_last_date'] = 0;
			self::$runtime['cron_2_last_date'] = 0;
			cache_set('runtime', self::$runtime);
		}
		// hook model_runtime_init_end.php
		MyApp::shutdown(array(self::class,'save'),'runtime');
		return self::$runtime;
	}
	/**
	 * 读取统计数据
	 */
	public static function getItem($k)
	{
		// hook model_runtime_get_start.php
		// hook model_runtime_get_end.php
		return array_value(self::$runtime, $k, NULL);
	}
	/**
	 * 设置统计数据
	 */
	public static function setItem($k, $v)
	{
		// hook model_runtime_set_start.php
		$op = substr($k, -1);
		if ($op == '+' || $op == '-') {
			$k = substr($k, 0, -1);
			!isset(self::$runtime[$k]) and self::$runtime[$k] = 0;
			$v = $op == '+' ? (self::$runtime[$k] + $v) : (self::$runtime[$k] - $v);
		}

		self::$runtime[$k] = $v;
		return TRUE;
		// hook model_runtime_set_end.php
	}
	/**
	 * 删除统计数据
	 */
	public static function removeItem($k)
	{
		// hook model_runtime_delete_start.php
		unset(self::$runtime[$k]);
		self::save();
		return TRUE;
		// hook model_runtime_delete_end.php
	}

	/**
	 * 保存统计数据
	 */
	public static function save()
	{
		// hook model_runtime_save_start.php
		$r = cache_set('runtime', self::$runtime);

		// hook model_runtime_save_end.php
	}
	/**
	 * 清空统计数据
	 */
	public static function clear()
	{
		// hook model_runtime_truncate_start.php
		cache_delete('runtime');
		// hook model_runtime_truncate_end.php
	}



	// hook model_runtime_end.php

	// hook model_cron_start.php

	/**
	 * 计划任务
	 */
	public static function cron($force = 0)
	{
		// hook model_cron_run_start.php
		global $conf, $forumlist;
		$cron_1_last_date = self::getItem('cron_1_last_date');
		$cron_2_last_date = self::getItem('cron_2_last_date');

		$t = $_SERVER['REQUEST_TIME'] - $cron_1_last_date;

		// 每隔 5 分钟执行一次的计划任务
		if ($t > 300 || $force) {
			$lock = cache_get('cron_lock_1');
			if ($lock === NULL) {
				cache_set('cron_lock_1', 1, 10); // 设置 10 秒超时

				MyApp::app()->sess_gc($conf['online_hold_time']);

				self::$runtime['onlines'] = max(1, online_count());

				self::setItem('cron_1_last_date', $_SERVER['REQUEST_TIME']);

				// hook model_cron_5_minutes_end.php

				cache_delete('cron_lock_1');
			}
		}

		// 每日 0 点执行一次的计划任务
		$t = $_SERVER['REQUEST_TIME'] - $cron_2_last_date;
		if ($t > 86400 || $force) {

			$lock = cache_get('cron_lock_2'); // 高并发下, mysql 机制实现的锁锁不住，但是没关系
			if ($lock === NULL) {
				cache_set('cron_lock_2', 1, 10); // 设置 10 秒超时

				// 每日统计清 0
				self::setItem('todayposts', 0);
				self::setItem('todaythreads', 0);
				self::setItem('todayusers', 0);

				foreach ($forumlist as $fid => $forum) {
					forum__update($fid, array('todayposts' => 0, 'todaythreads' => 0));
				}
				forum_list_cache_delete();

				// 清理临时附件
				attach_gc();

				// 清理过期的队列数据
				queue_gc();

				list($y, $n, $d) = explode(' ', date('Y n j', $_SERVER['REQUEST_TIME'])); 	// 0 点
				$today = mktime(0, 0, 0, $n, $d, $y);			// -8 hours
				self::setItem('cron_2_last_date', $today, TRUE);		// 加到1天后

				// 往前推8个小时，尽量保证在前一天
				// 如果是升级过来和采集的数据，这里会很卡。
				// table_day_cron($_SERVER['REQUEST_TIME'] - 8 * 3600);

				// hook model_cron_daily_end.php

				cache_delete('cron_lock_2');
			}
		}
		// hook model_cron_run_end.php
	}



	// hook model_cron_end.php

}