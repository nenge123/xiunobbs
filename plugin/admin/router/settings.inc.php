<?php
defined('XIUNO')||die('return to <a href="">Home</a>');
if($myapp['method']=='POST'&&!empty($_POST)){
    foreach($_POST as $k=>$v){
        $data[] = array('name'=>$k,'value'=>$v,'type'=>0);
    }
    $myapp->DB('Insert','settings',$data,true);
    $myapp->getCache('settings',true);

}
/*
$settings = array();
foreach($myapp->DB('FetchAll','settings',array('type'=>0)) as $k=>$v){
    $settings[$v['name']] = $v['value'];
}
#print_r($settings);
*/
$settings = $myapp['settings'];
$text = array(
    'type'=>'text',
    'value'=>'',
);
$textarea = array(
    'type'=>'textarea',
    'value'=>'',
);
$settings_list = array(
    'site'=>array(
        'site_close'=>array(
            'type'=>'radio',
            'option'=>array(
                0=>$language['site_close_0'],
                1=>$language['site_close_1'],
            ),
            'defalut'=>0,
        ),
        'site_name'=>array('value'=>$language['site_name'])+$text,
        'site_keywords'=>array('value'=>$language['site_keywords'])+$text,
        'site_description'=>$textarea,
        'site_template'=>array('value'=>'index')+$text,
        'site_rewrite'=>array(
            'type'=>'select',
            'option'=>array(
                0 => $language["site_rewrite_0"],
                1 => $language["site_rewrite_1"],
                2 => $language["site_rewrite_2"],
                3 => $language["site_rewrite_3"],
            ),
            'defalut'=>0,
        ),
        'site_logp'=>array('value'=>'logo.png')+$text,
    ),
    'language'=>array(
        'lang_name'=>array(
            'type'=>'select',
            'option'=>$myapp->F('list_dirname',$myapp->path->lang),
            'defalut'=>'zh-cn',
            'key'=>true,
        ),
        'lang_auto'=>array(
            'type'=>'radio',
            'option'=>array(
                0=>$language['site_close_1'],
                1=>$language['site_close_0'],
            ),
            'defalut'=>0,
        ),
    ),
    'privacy'=>array(
        'cookie_login'=>array(
            'type'=>'select',
            'option'=>array(
                0 => $language["cookie_login_0"],
                1 => $language["cookie_login_1"],
                2 => $language["cookie_login_2"],
            ),
            'defalut'=>0,
        ),
        'cookie_domain'=>$text,
        'cookie_path'=>array(
            'type'=>'radio',
            'option'=>array(
                0=>$language['site_close_1'],
                1=>$language['site_close_0'],
            ),
            'defalut'=>0,
        ),
        'cookie_prefix'=>$text,
        'encrypt_key'=>$text,
        'encrypt_method' =>array('value'=> "AES-256-CBC")+$text,
    ),
    'time'=>array(
        'time_zone'=>array(
            'type'=>'select',
            'option'=>$myapp->F('list_timezone'),
            'defalut'=>'Asia/Shanghai',
        ),
        'time_format'=>array('value'=>'Y/m/d H:i:s')+$text,
        'time_human'=>array(
            'type'=>'radio',
            'option'=>array(
                0 => $language["site_close_1"],
                1 => $language["site_close_0"],
            ),
            'defalut'=>0,
        ),
    ),
    'forum'=>array(
        'forum_size'=>array('value'=>15)+$text,
        'forum_template'=>array('value'=>'forum')+$text,
        'forum_order'=>array('value'=>'last_date,DESC')+$text,
        'forum_info'=>array(
            'type'=>'radio',
            'option'=>array(
                1 => $language["site_close_0"],
                0 => $language["site_close_1"],
            ),
            'defalut'=>0,
        ),
    ),
    'thread'=>array(
        'thread_size'=>array('value'=>15)+$text,
        'thread_template'=>array('value'=>'thread')+$text,
        'thread_order'=>array('value'=>'create_date,ASC')+$text,
    ),
    'post'=>array(
        'post_size'=>array('value'=>0)+$text,
        'post_template'=>array('value'=>'list/postleft')+$text,
    ),
    'attach'=>array(
        'attach_radio'=>array(
            'type'=>'radio',
            'option'=>array(
                1 => $language["site_close_0"],
                0 => $language["site_close_1"],
            ),
            'defalut'=>1,
        ),
        'attach_dir'=>array('value'=>'Ym')+$text,
        'attach_size'=>array('value'=>0)+$text,
        'attach_type'=>array('value'=>'zip,txt,png,jpg,gif,webp')+$text,
    ),
    'update'=>array(
        'update_online'=>array('value'=>900)+$text,
        'update_corn'=>array('value'=>900)+$text,
    ),
    'verify'=>array(
        'verify_email'=>array(
            'type'=>'radio',
            'option'=>array(
                0 => $language["site_close_1"],
                1 => $language["site_close_0"],
            ),
            'defalut'=>0,
        ),
        'verify_captcha'=>array(
            'type'=>'radio',
            'option'=>array(
                0 => $language["site_close_1"],
                1 => $language["site_close_0"],
            ),
            'defalut'=>0,
        ),
    )
);
include $myapp->getTemplate('admin:settings');