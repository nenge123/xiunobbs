<?php
$myapp['title']= $language['admin_index'];
$datalist = [];
if (!in_array($scriptDo, ['base', 'smtp', 'read'])) {
    $scriptDo = 'base';
}
if ($scriptDo == 'base') {
    $langdir = [];
    $dh = opendir($myapp->path->lang);
    while (($dirpath = readdir($dh)) !== false) {
        if (!in_array($dirpath, ['.', '..']) && is_dir($myapp->path->lang . $dirpath)) {
            $dirpath2 = 'lang_' . str_replace('-', '_', $dirpath);
            $langdir[$dirpath] = empty($language[$dirpath2]) ? $dirpath : $language[$dirpath2];
        }
    }
    closedir($dh);
    $datalist[] = array(
        'text' => $language['admin_setting_base'],
        'data' => array(
            'sitename' => array(
                'text' => $language["sitename"],
                'holder' => '',
                'more' => '',
                'type' => 'input',
            ),
            'sitebrief' => array(
                'text' => $language["sitebrief"],
                'holder' => '',
                'more' => $language["sitebrief_tips"],
                'type' => 'textarea',
            ),
            'runlevel' => array(
                'text' => $language["runlevel"],
                'more' => '',
                'type' => 'radio',
                'value' => array(
                    0 => $language["runlevel_0"],
                    1 => $language["runlevel_1"],
                    2 => $language["runlevel_2"],
                    3 => $language["runlevel_3"],
                    4 => $language["runlevel_4"],
                    5 => $language["runlevel_5"],
                ),
                'default' => 5,
            ),
            'user_create_on' => array(
                'text' => $language["user_create_on"],
                'more' => '',
                'type' => 'radio',
                'value' => array(
                    0 => $language["no"],
                    1 => $language['yes'],
                ),
                'default' => 1,
            ),
            'user_create_email_on' => array(
                'text' => $language["user_create_email_on"],
                'more' => '',
                'type' => 'radio',
                'value' => array(
                    0 => $language["no"],
                    1 => $language['yes'],
                ),
                'default' => 1,
            ),
            'user_resetpw_on' => array(
                'text' => $language["user_resetpw_on"],
                'more' => '',
                'type' => 'radio',
                'value' => array(
                    0 => $language["no"],
                    1 => $language['yes'],
                ),
                'default' => 0,
            ),
            'lang' => array(
                'text' => $language["lang"],
                'type' => 'select',
                'value' => $langdir,
                'default' => 'zh-cn',
            ),
            'lang_auto' => array(
                'text' => $language["lang_auto"],
                'more' => $language["lang_auto_tips"],
                'type' => 'radio',
                'value' => array(
                    0 => $language["no"],
                    1 => $language['yes'],
                ),
                'default' => 0,
            ),
            'mobiletemplate' => array(
                'text' => $language["mobiletemplate"],
                'more' => $language["mobiletemplate_tips"],
                'type' => 'radio',
                'value' => array(
                    0 => $language["no"],
                    1 => $language['yes'],
                ),
                'default' => 0,
            ),
            'router_hook' => array(
                'text' => $language["router_hook"],
                'more' => $language["router_hook_tips"],
                'type' => 'radio',
                'value' => array(
                    0 => $language["no"],
                    1 => $language['yes'],
                ),
                'default' => 1,
            ),
        )
    );
    $timezone_identifiers = DateTimeZone::listIdentifiers();
    foreach ($timezone_identifiers as $v) {
        $tzStr = array_map(fn ($m) => empty($language[$m]) ? $m : $language[$m], explode('/', $v));
        $timezone_data[$v] = implode('/', $tzStr);
    }
    $datalist[] = array(
        'text' => $language['admin_time'],
        'data' => array(
            'timezone' => array(
                'text' => $language['timezone'],
                'type' => 'select',
                'value' => $timezone_data,
                'default' => 'Asia/Shanghai',
            ),
            'timeformat'=>array(
                'text' => $language['timeformat'],
                'holder' => 'Y/m/d H:i:s',
                'more' => $language['timeformat_tips'],
                'type' => 'input',
                'default' => 'Y/m/d H:i:s',
            ),
            'timehuman'=>array(
                'text' => $language['timehuman'],
                'more' => $language['timehuman_tips'],
                'type' => 'radio',
                'value' => array(
                    0 => $language["no"],
                    1 => $language['yes'],
                ),
                'default' => 1,
            ),
            
        )
    );
    $datalist[] = array(
        'text' => $language['admin_rewrite'],
        'data' => array(
            'site_rewrite' => array(
                'text' => $language['rewrite_on'],
                'more' => '',
                'type' => 'select',
                'value' => array(
                    0 => $language["rewrite_on_0"],
                    1 => $language["rewrite_on_1"],
                    2 => $language["rewrite_on_2"],
                    3 => $language["rewrite_on_3"],
                ),
                'default' =>'0',
            ),
        )
    );
    $datalist[] = array(
        'text'=>$language["admin_online"],
        'data'=>array(
            'online_hold_time' => array(
                'text' => $language["online_hold_time"],
                'holder' => '',
                'more' => $language["online_hold_time_tips"],
                'type' => 'input',
                'default' =>'15',
            ),
            'online_update_span' => array(
                'text' => $language["online_update_span"],
                'holder' => '',
                'more' => $language["online_update_span_tips"],
                'type' => 'input',
                'default' =>'15',
            ),
            'online_cache' => array(
                'text' => $language["online_cache"],
                'holder' => '',
                'more' => $language["online_cache_tips"],
                'type' => 'input',
                'default' =>'mysql',
            ),
        )
    );
} elseif ($scriptDo == 'smtp') {
    $smtplist = empty($myapp['settings']['smtp']) ? array() : $myapp['settings']['smtp'];
    if (is_string($smtplist)) $smtplist = unserialize($smtplist);
} elseif ($scriptDo == 'read') {
    $datalist = array(
        0 => array(
            'text' => $language['admin_setting_thread'],
            'data' => array(
                'thread_order' => array(
                    'text' => $language['thread_order'],
                    'more' => $language['thread_order_tips'],
                    'type' => 'select',
                    'value' => array(
                        'last_date' => $language['thread_order_lastdate'],
                        'create_date' => $language['thread_order_createdate']
                    ),
                    'default' => 'last_date',
                ),
                'thread_size' => array(
                    'text' => $language['thread_size'],
                    'more' => $language['thread_size_tips'],
                    'type' => 'input',
                    'holder' => $language['thread_size'],
                    'default' => 20,
                ),
                'forum_showinfo'=>array(
                    'text' => $language['forum_showinfo'],
                    'more' => $language['forum_showinfo_tips'],
                    'type' => 'radio',
                    'value' => array(
                        0 => $language["no"],
                        1 => $language['yes'],
                    ),
                    'default' => 1,

                ),
                'update_views_on'=>array(
                    'text' => $language['update_views'],
                    'more' => $language['update_views_tips'],
                    'type' => 'radio',
                    'value' => array(
                        0 => $language["no"],
                        1 => $language['yes'],
                    ),
                    'default' => 1,

                ),
            )
        ),
        1=>array(
            'text'=> $language['admin_setting_post'],
            'data'=>array(
                'post_order' => array(
                    'text' => $language['post_order'],
                    'more' => $language['post_order_tips'],
                    'type' => 'select',
                    'value' => array(
                        'DESC' => $language['post_order_lastid'],
                        'ASC' => $language['post_order_new']
                    ),
                    'default' => 'DESC',
                ),
                'post_size' => array(
                    'text' => $language['post_size'],
                    'more' => $language['post_size_tips'],
                    'type' => 'input',
                    'holder' => $language['post_size'],
                    'default' => 30,
                ),
                'post_limit' => array(
                    'text' => $language['post_limit'],
                    'more' => $language['post_limit_tips'],
                    'type' => 'input',
                    'default' => 0,
                ),
                'post_limitmode' => array(
                    'text' => $language['post_limitmode'],
                    'more' => $language['post_limitmode_tips'],
                    'type' => 'select',
                    'value' => array(
                        'tid' => $language['thread'],
                        'fid' => $language['forum'],
                        '' => $language['all']
                    ),
                    'default' => '',
                ),
                /*
                'post_reedit' => array(
                    'text' => $language['post_limitmode'],
                    'more' => $language['post_limitmode_tips'],
                    'type' => 'select',
                    'value' => array(
                        'tid' => $language['thread'],
                        'fid' => $language['forum'],
                        '' => $language['all']
                    ),
                    'default' => '',
                ),
                'post_delete' => array(
                    'text' => $language['post_limitmode'],
                    'more' => $language['post_limitmode_tips'],
                    'type' => 'select',
                    'value' => array(
                        'tid' => $language['thread'],
                        'fid' => $language['forum'],
                        '' => $language['all']
                    ),
                    'default' => '',
                ),
                */
            )
        ),
        3=>array(
            'text'=> $language['admin_setting_attach'],
            'data'=>array(
                'upload_image_width' => array(
                    'text' => $language['upload_image_width'],
                    'more' => $language['upload_image_width_tips'],
                    'type' => 'input',
                    'default' => 1080,
                ),
                'attach_dir_save_rule' => array(
                    'text' => $language['attach_dir_save_rule'],
                    'more' => $language['attach_dir_save_rule_tips'],
                    'type' => 'input',
                    'default' => 'Ym',
                ),
                'attach_mime' => array(
                    'text' => $language['attach_mime'],
                    'more' => $language['attach_mime_tips'],
                    'type' => 'input',
                    'default' => 'zip,rar,7z,jpg,png,gif,webp',
                ),
            )
        ),
    );
}
if (!empty($datalist)) {
    $postlist = [];
    foreach ($datalist as $v) {
        if (!empty($v['data'])) {
            $postlist += $v['data'];
        }
    }
    foreach(array_keys($postlist) as $v){
        if(!isset($myapp['settings'][$v])){
            $myapp['settings'][$v] = !isset($postlist[$v]['default'])?'':$postlist[$v]['default'];
        }
    }
}
if ($myapp['method'] == 'POST') {
    $settings_arr = [['name', 'value']];
    if (!empty($datalist)) {
        foreach (array_keys($postlist) as $v) {
            if (isset($_POST[$v])) {
                $value = trim($_POST[$v]);
                if (!empty($postlist[$v]['default'])) {
                    if (!empty($postlist[$v]['value'])) {
                        if (!in_array($value, array_keys($postlist[$v]['value']))) {
                            $value = $postlist[$v]['default'];
                        }
                    } elseif ($value == "") {
                        $value = $postlist[$v]['default'];
                    }
                }
                if ($v == 'lang') {
                    if (!is_dir($myapp->path->lang . $value) || !is_dir($myapp->path->lang . $value . '/bbs.php') || !is_dir($myapp->path->lang . $value . '/bbs_admin.php')) {
                        $value = $postlist[$v]['default'];
                    }
                }
                $settings_arr[] = array($v, $value);
            }
        }
    }
    if (!empty($_POST['smtp'])) {
        $smtplist = [];
        foreach ($_POST['smtp'] as $k => $v) {
            foreach ($v as $a => $b) {
                $smtplist[$a][$k] = $b;
            }
        }
        if (!empty($smtplist)) {
            $settings_arr[] = array('smtp', $smtplist);
        }
        #print_r($smtplist);exit;
    }
    #print_r($_POST);exit;
    if (count($settings_arr) > 1) {
        $myapp->DB('Insert', 'settings', $settings_arr, true);
        $myapp->getCache('settings', true);
    }
}
    //print_r(addcslashes($myapp->site->root,'/[].$()').'(('.implode('|',$router_list).')((-|\/).+?)?\.?(htm|html|php)?\/?)');
#echo $myapp['settings']['lang'];
include $myapp->getTemplate('admin/setting');
