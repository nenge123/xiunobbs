<?php
!defined('DEBUG') AND exit('Access Denied.');

$action = param(3);
empty($action) AND $action = 'set';

if ($action === 'set') {
    if ($method == 'GET') {
        $kv = kv_get('sitemap');
        $input = array();
        $input['domain'] = form_text('domain', $kv['domain']);
        $input['maxsize'] = form_text('maxsize', $kv['maxsize']);
        include _include(APP_PATH . 'plugin/zls_sitemap/view/htm/setting.htm');
    } else {
        $kv = array();
        $kv['domain'] = param('domain', '');
        $kv['maxsize'] = param('maxsize', 0);
        empty($kv['domain']) AND $kv['domain'] = http_root_path();
        ($kv['maxsize'] === 0 OR $kv['maxsize'] >= 50000) AND $kv['maxsize'] = 50000;
        kv_set('sitemap', $kv);
        message(0, '修改成功');
    }
} elseif ($action === 'generate') {
    if ($method == 'GET') {
        $kv = kv_get('sitemap');
        $input = array();
        $input['type'] = form_radio('type', array(1 => 'xml文件'), 1);
        include _include(APP_PATH . 'plugin/zls_sitemap/view/htm/setting_generate.htm');
    } else {
        include _include(APP_PATH . 'plugin/zls_sitemap/sitemap.class.php');
        $kv = kv_get('sitemap');
        $kv === null && message(-1, '请移步[基础设置]');
        $sitemap = new Sitemap($kv['domain'], $kv['maxsize']);

        $gid = 0;
        $forumlist = forum_list_cache();
        $forumlist_show = forum_list_access_filter($forumlist, $gid);
        $fids = arrlist_values($forumlist_show, 'fid');
        $threadlist = db_sql_find('SELECT `fid`,`tid` FROM `bbs_thread` WHERE `fid` IN (' . implode(',', $fids) . ') ORDER BY `tid` DESC');
        thread_list_access_filter($threadlist, $gid);

        foreach ($threadlist as &$_thread) {
            $sitemap->addItem($kv['domain'] . url('thread-' . $_thread['tid']));
        }
        $sitemap->store('xml', 'sitemap') ? message(0, '创建成功') : message(-1, '创建失败');
    }
} elseif ($action === 'about') {
    include _include(APP_PATH . 'plugin/zls_sitemap/view/htm/setting_about.htm');
}
?>