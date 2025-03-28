<?php
namespace model;

use MyApp;
use MyDB;
/**
 * 版块处理函数
 */
class forum{
	public static function format(&$forum) {
		if(empty($forum)) return;		
		// hook model_forum_format_start.php
		$forum['create_date_fmt'] = date('Y-n-j', $forum['create_date']);
		$forum['icon_url'] = $forum['icon'] ? MyApp::upload_site('forum/'.$forum['fid'].'.png') :MyApp::view_site('img/forum.png');
		$forum['accesslist'] = $forum['accesson'] ? forum_access_find_by_fid($forum['fid']) : array();
		$forum['modlist'] = array();
		if($forum['moduids']) {
			$modlist = user_find_by_uids($forum['moduids']);
			foreach($modlist as &$mod) $mod = user_safe_info($mod);
			$forum['modlist'] = $modlist;
		}
		// hook model_forum_format_end.php
	}
}