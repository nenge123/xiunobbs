<?php

return array(
	'user'=>'用户',
	'forum'=>'版块',
	'plugin'=>'插件',
	'other'=>'其他',
	'buy'=>'Buy',
	
	'user_admin'=>'用户管理',
	'group_admin'=>'用户组管理',
	'forum_admin'=>'版块管理',
	'post_admin'=>'帖子管理',
	'thread_admin'=>'主题管理',
	'plugin_admin'=>'插件管理',
	'other_admin'=>'其他管理',
	
	'admin_index_page'=>'后台',
	'front_index_page'=>'前台',
	'admin_site_setting'=>'站点设置',
	'admin_setting_base'=>'基本设置',
	'admin_setting_smtp'=>'SMTP 设置',
	'admin_setting_time'=>'时间设置',
	'admin_setting_rewrite'=>'伪静态设置',
	'admin_other_cache'=>'清理缓存',
	'admin_clear_tmp'=>'清理临时目录',
	'admin_clear_cache'=>'清理缓存',
	'admin_clear_successfully'=>'清理成功',
	'admin_forum_setting'=>'版块设置',
	
	'admin_user_list'=>'用户列表',
	'admin_thread_batch'=>'主题批量管理',
	'admin_user_group'=>'用户组',
	'admin_user_create'=>'创建用户',
	'admin_plugin_local_list'=>'本地插件',
	'admin_plugin_official_list'=>'官方插件',
	'admin_plugin_official_free_list'=>'免费插件',
	'admin_plugin_official_fee_list'=>'收费插件',
	
	'admin_token_error'=>'管理令牌错误，可能因为您的网络环境不稳定，可以尝试取消后台绑定 IP，配置 conf.php，  => 0 ',
	'admin_token_expiry'=>'管理登陆令牌失效，请重新登录',
	'forum_edit'=>'版块编辑',
	'user_edit'=>'用户编辑',
	'edit_sucessfully'=>'编辑成功',
	'item_not_exists'=>'{item} 不存在',
	'item_not_moderator'=>'{item} 不是版主',
	'group_not_exists'=>'用户组不存在',
	
	'admin_login'=>'管理登陆',
	'save_conf_failed'=>'保存数据到配置文件 {file} 失败，请检查文件的可写权限',
	'user_already_exists'=>'用户已经存在',
	'email_already_exists'=>'邮箱已经存在',
	'uid_not_exists'=>'指定的 UID 不存在',
	'data_not_changed'=>'没有数据变动',
	'admin_cant_be_deleted'=>'不能直接删除管理员，请先编辑为普通用户组',
	
	// 首页
	'admin_index'=>'后台首页',
	'site_stat_info'=>'站点统计信息',
	'disk_free_space'=>'磁盘剩余空间',
	'server_info'=>'服务器信息',
	'os'=>'操作系统',
	'post_max_size'=>'最大 POST 数据大小',
	'upload_max_filesize'=>'最大文件上传大小',
	'allow_url_fopen'=>'允许开启远程 URL',
	'safe_mode'=>'安全模式（safe_mode）',
	'max_execution_time'=>'最长执行时间',
	'memory_limit'=>'内存上限',
	'client_ip'=>'客户端 IP',
	'server_ip'=>'服务端 IP',
	'dev_team_info'=>'开发团队信息',
	
	'for_safe_input_password_again'=>'为了您的安全，请再次输入账户密码',
	
	// 设置
	'sitename'=>'站点名称',
	'sitebrief'=>'站点介绍',
	'sitebrief_tips'=>'注：支持 HTML 标签，换行请使用 &lt;br&gt;',
	'runlevel'=>'站点访问限制',
	'user_create_on'=>'开启用户注册',
	'user_create_not_on'=>'未开启用户注册',
	'user_create_email_on'=>'开启注册邮箱验证',
	'user_resetpw_on'=>'开启找回密码',
	'lang'=>'语言',
	'database'=>'数据库',
	'host'=>'主机',
	'port'=>'端口',
	'account'=>'账号',
	'smtp_host'=>'SMTP 主机',
	'setting_timezone'=>'时区设置',
	'online_hold_time'=>'session保留多少秒',
	'online_update_span'=>'session更新频率',
	'url_rewrite_on'=>'启用伪静态 /thread-1.html',
	'url_rewrite_style'=>'PATH静态 /index.php/thread-1.html',
	'url_rewrite_style_msg'=>'同时启用,只有伪静态生效!<br>但是伪静态必须设置重写规则!<br>重写规则还可以防御用户而已访问内部文件,从而窃取信息!<br>伪静态规则 /abc/cdf/hjk.html 和 /abc-cdf-hjk.html结果一致,但与/abc/cdf-hjk.html不一致会被理解成abc cdf-hjk',
	'cdn_on'=>'cdn访问(可能废弃)',
	'conf_view_url'=>'view目录的网络HTTP地址',
	'conf_upload_url'=>'upload目录的网络HTTP地址',
	'conf_upload_url_msg'=>'原有属性作用废弃.<br>此地址必须是HTTP网络地址:<b>https://cdn.yourname.com/(末尾带斜杠)</b><br>否则以物理目录位置自动设置.',
	'conf_view_path'=>'view目录物理地址:(默认值view/)',
	'conf_upload_path'=>'upload目录物理地址:(默认值upload/)',
	'conf_htm_path'=>'htm目录物理地址:(默认值view/htm/)',
	'conf_htm_path_msg'=>'相对网站根目录,一般情况下保持默认值,修改后需要修改对应目录,一般保持默认即可!',
	
	// 版块
	'forum_list'=>'版块列表',
	'forum_id'=>'版块 ID',
	'forum_icon'=>'图标',
	'forum_name'=>'名称',
	'forum_rank'=>'排序',
	'forum_edit'=>'编辑',
	'forum_delete'=>'删除',
	'forum_brief'=>'简介',
	'forum_announcement'=>'公告',
	'moderator'=>'版主',
	'add_new_line'=>'增加一行',
	'forum_edit_tip'=>'请谨慎编辑版块，一旦确定后不要轻易变动，否则可能会导致数据关联错误，一般在正式运营时就不要再变动。',
	'forum_cant_delete_system_reserved'=>'不能删除系统保留的版块。',
	'forum_moduid_format_tips'=>'最多允许10个，逗号隔开，如：Jack,Lisa,Mike,亦可以输入UID',
	'admin_forum_edit'=>'板块编辑',
	'admin_forum_delete'=>'删除板块',
	'admin_forum_delete_message'=>'你确定你没有手滑删除吗?',
	'announcement_edit'=>'点击编辑板块公告',
	'admin_forum_save_brief'=>'保存成功!<br>缓存原因,效果不会立即在板块显示!<br>可以手动更新缓存或者在修改板块信息时更新.',
	'brief_edit'=>'点击编辑板块说明',
	'user_privilege'=>'用户权限',
	'allow_view'=>'允许看帖',
	'allow_thread'=>'发主题',
	'allow_post'=>'回贴',
	'allow_upload'=>'上传',
	'allow_download'=>'下载',
	'forum_delete_thread_before_delete_forum'=>'请先通过批量主题管理删除版块主题。',
	'forum_please_delete_sub_forum'=>'请删除子版块。',
	'forum_delete_successfully'=>'删除成功。',
	'forum_delete_error'=>'删除失败。',
	'forum_no_update'=>'数据没有发生变化',
	
	// 主题
	'thread_queue_not_exists'=>'队列不存在',
	'search_condition'=>'搜索条件',
	'start_date'=>'开始时间',
	'end_date'=>'结束时间',
	'searching'=>'正在搜索',
	'operating'=>'正在操作',
	'operator_complete'=>'操作完成',
	'click_to_view'=>'点击查看',
	'thread_userip'=>'发帖 IP',
	'thread_delete'=>'删除',
	'thread_search_result'=>'结果：{n} 条',
	'admin_thread_delete'=>'主题删除',
	'admin_thread_delete_message'=>'此操作不可恢复!!',
	
	// 用户
	'please_check_delete_user'=>'请勾选您要删除的用户',
	'user_delete_confirm'=>'确定删除用户？',
	'user_admin_cant_be_deleted'=>'不允许删除管理员用户，如果确实要删除，请先调整用户组!',
	'search_type'=>'搜索类型',
	'user_privileges'=>'用户权限',
	'user_block'=>'你已封禁{n}个用户!',
	'author'=>'作者',
	'user-key-uid'=>'用户ID',
	'user-key-gid'=>'用户组ID',
	'user-key-username'=>'账号',
	'user-key-password'=>'密码',
	'user-key-salt'=>'盐/修改密码标识',
	'user-key-email'=>'邮址',
	'user-key-mobile'=>'手机',
	'user-key-qq'=>'QQ',
	'user-key-threads'=>'主题数量',
	'user-key-posts'=>'回帖数量',
	'user-key-credits'=>'积分',
	'user-key-golds'=>'金币',
	'user-key-rmbs'=>'软妹币',
	'user-key-login_ip'=>'登录IP',
	'user-key-create_ip'=>'注册IP',
	'user-key-create_date'=>'注册时间',
	'user-key-login_date'=>'登录时间',
	'user-key-logins'=>'登录次数',
	'user-key-avatar'=>'头像标识',
	'user-key-realname'=>'真实名字',
	'user-key-idnumber'=>'身份id',
	'user-key-password_sms'=>'短信密码',
	'user-create-uid-msg'=>'UID若非必要,留空,或者默认值.如果你添加了一个UID=1000,往后网站用户注册将以1001开始!',
	
	// 用户组
	'group_list'=>'用户组列表',
	
	'group_edit'=>'用户组编辑',
	'group_id'=>'用户组 ID',
	'group_name'=>'用户组名',
	'group_credits_from'=>'起始积分',
	'group_credits_to'=>'结束积分',
	'group_id'=>'用户组ID',
	'group_edit_tips'=>'请谨慎编辑用户组，一旦确定后不要轻易变动，否则可能会导致用户关联错误，一般在正式运营时就不要再变动。',
	'admin_privilege'=>'管理权限',
	'top'=>'置顶',
	'ban_user'=>'禁止用户',
	'unban_user'=>'解封用户',
	'delete_user'=>'删除用户',
	'view_user_info'=>'查看用户信息',
	'group_system_list'=>'系统用户组',
	'group_member_list'=>'自定义用户组',
	'group-key-gid'=>'用户组ID',
	'group-key-name'=>'用户组名称',
	'group-key-creditsfrom'=>'起始积分',
	'group-key-creditsto'=>'结束积分',
	'group-key-allowread'=>'阅读权限',
	'group-key-allowthread'=>'发帖权限',
	'group-key-allowpost'=>'回帖权限',
	'group-key-allowattach'=>'上传权限',
	'group-key-allowdown'=>'下载权限',
	'group-key-allowtop'=>'置顶权限',
	'group-key-allowupdate'=>'更新权限',
	'group-key-allowdelete'=>'删除权限',
	'group-key-allowmove'=>'移动权限',
	'group-key-allowbanuser'=>'封禁权限',
	'group-key-allowdeleteuser'=>'删号权限',
	'group-key-allowviewip'=>'IP权限',
	'credits-auto-update'=>'取消积分自动更新',
	
	// 插件
	'plugin_dir'=>'插件目录名',
	'plugin_bbs_version'=>'要求 BBS 最低版本',
	'price'=>'价格',
	'installs'=>'安装次数',
	'plugin_user_stars_fmt'=>'用户评级',
	'plugin_sells'=>'销售次数',
	'plugin_is_cert'=>'通过官方认证',
	'local_plugin'=>'本地插件',
	'official_plugin'=>'官方插件',
	'pugin_cate_0'=>'所有插件',
	'pugin_cate_1'=>'风格模板',
	'pugin_cate_2'=>'小型插件',
	'pugin_cate_3'=>'大型插件',
	'pugin_cate_4'=>'接口整合',
	'pugin_cate_99'=>'未分类',
	'plugin_detail'=>'插件详情',
	'plugin_brief_url'=>'插件介绍网址',
	'plugin_not_exists'=>'插件不存在',
	'plugin_versio_not_match'=>'此插件依赖的 Xiuno BBS 最低版本为 {bbs_version} ，您当前的版本：{version}',
	'plugin_download_sucessfully'=>'下载插件 ({dir}) 成功，请点击进行安装',
	'plugin_install_sucessfully'=>'安装插件 ( {name} ) 成功',
	'plugin_unstall_sucessfully'=>'卸载插件 ( {name} ) 成功，要彻底删除插件，请手工删除 {dir} 目录',
	'plugin_enable_sucessfully'=>'启用插件 ( {name} ) 成功',
	'plugin_disable_sucessfully'=>'禁用插件 ( {name} ) 成功',
	'plugin_upgrade_sucessfully'=>'升级插件 ( {name} ) 成功',
	'plugin_not_need_update'=>'已经是最新版本，无需更新',
	'plugin_set_relatied_dir_writable'=>'在安装插件目录期间，请设置：{dir} 和文件为可写',
	'plugin_dependency_following'=>'依赖以下插件：{s}，请先安装依赖的插件',
	'plugin_being_dependent_cant_delete'=>'不能删除 {name}，以下插件依赖它：{s}',
	'server_response_empty'=>'服务器返回数据为空',
	'server_response_error'=>'服务器返回数据有错',
	'zip_data_error'=>'压缩包数据有误',
	'format_maybe_error'=>'格式可能不正确',
	'plugin_maybe_download_failed'=>'插件可能下载失败，目录不存在:',
	'plugin_name_error'=>'插件名不合法',
	'plugin_unstall_confirm_tips'=>'卸载会清理该插件相关数据，确定卸载 ( {name} ) 吗？',
	'plugin_task_locked'=>'另外一个插件任务正在执行，当前任务被锁住。',
	'plugin_return_data_error'=>'返回数据有误：',
	'plugin_is_free'=>'该插件免费。',
	'plugin_is_not_free'=>'该插件需要付费购买，请先支付。',
	'plugin_is_bought'=>'已经购买过。',
	'plugin_not_bought'=>'还没购买过。',
	'plugin_wechat_qrcode_pay'=>'微信扫码支付。',
	'plugin_service_qq'=>'客服 QQ',
	'admin_plugin_all'=>'所有插件',
	'admin_plugin_enable'=>'已启用',
	'admin_plugin_disable'=>'已关闭',
	'admin_plugin_install'=>'已安装',
	'admin_plugin_unstall'=>'未安装',

	
	'error_request'=>'异常请求',
	'forum_event_stream_start'=>'正在为你删除板块',
	'forum_event_stream_progress'=>'存在主题,正在删除%s条主题',
	'forum_event_stream_progress_subject'=>'正在删除主题: %s  ...',
	'forum_event_stream_progress_success'=>'删除%s条主题成功!稍后继续尝试删除板块!',
	'forum_event_stream_close'=>'板块已删除!正在返回...',
	'page_index'=>'首页',
	'page_last'=>'末页',
	// hook lang_zh_cn_bbs_admin.php
	
);

?>