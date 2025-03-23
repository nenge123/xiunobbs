<?php

namespace xn_search;

use MyDB;

class search
{
	//N修改开始
	public static function log($uid, $range, $keyword_decode)
	{
		//用户UID
		//搜索类型
		//搜索内容
		$mydb = MyDB::t('search_log');
		if (empty($uid)):
			$where = array('clientip' => $_SERVER["REMOTE_ADDR"]);
		else:
			$where = array('userid' => intval($uid));
		endif;
		//查询用户上一次搜索间隔
		$datatime = $mydb->whereMax($where, 'datetime');
		if ($datatime):
			if ($datatime + 10 > $_SERVER['REQUEST_TIME']):
				return 3;
			else:
				//查询当前用户搜索次数
				$number = $mydb->whereCount(array_merge(['>datetime' => mktime(0, 0, 0), '<datetime' => mktime(24, 0, 0)], $where));
				if ($number > 20 && $datatime + 30 > $_SERVER['REQUEST_TIME']):
					//用户当天搜索超过20次
					return 4; //搜索间隔少于30秒
				elseif ($number  > 40 && $datatime + 60 > $_SERVER['REQUEST_TIME']):
					//用户当天搜索超过40次
					return 5; //搜索间隔少于60秒
				elseif ($number > 50): //用户当天搜索超过50次
					return 6; //当天禁止该用户搜索功能
				endif;
			endif;
		endif;
		return 1;
		//写入搜索日志
		MyDB::t('search_log')->insert_json(
			array('clientip' => $_SERVER["REMOTE_ADDR"], 'datetime' => time(), 'userid' => $uid, 'type' => $range, 'content' => $keyword_decode)
		);
		return 1; //执行搜索
	}
	public static function conf(?array $data = null): mixed
	{
		$filepath = dirname(__DIR__) . '/conf/conf.php';
		if (empty($data)):
			if (is_file($filepath)):
				return include($filepath);
			endif;
			return array();
		else:
			return file_put_contents($filepath, '<?php return ' . var_export($data, true) . ' ;');
		endif;
	}
	public static function highlight($s, $keyword_arr) {
		foreach($keyword_arr as $keyword) {
			$s = str_ireplace($keyword, '<span class="text-danger">'.$keyword.'</span>', $s);
		}
		return $s;
	}
	public static function htmlformat($s) {
		$s = xn_substr(str_replace('&amp;nbsp;', ' ', htmlspecialchars(strip_tags($s))), 0, 200);
		return $s;
	}

}
