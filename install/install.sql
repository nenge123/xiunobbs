# Xiuno BBS 4.0 表结构

### 用户表 ###
DROP TABLE IF EXISTS `bbs_user`;
CREATE TABLE `bbs_user` (
  uid int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户编号',
  gid smallint UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户组编号',	# 如果要屏蔽，调整用户组即可
  email char(40) NOT NULL DEFAULT '' COMMENT '邮箱',
  username char(32) NOT NULL DEFAULT '' COMMENT '用户名',	# 不可以重复
  realname char(16) NOT NULL DEFAULT '' COMMENT '用户名',	# 真实姓名，天朝预留
  idnumber char(19) NOT NULL DEFAULT '' COMMENT '用户名',	# 真实身份证号码，天朝预留
  `password` char(32) NOT NULL DEFAULT '' COMMENT '密码',
  `password_sms` char(16) NOT NULL DEFAULT '' COMMENT '密码',	# 预留，手机发送的 sms 验证码
  salt char(16) NOT NULL DEFAULT '' COMMENT '密码混杂',
  mobile char(11) NOT NULL DEFAULT '' COMMENT '手机号',		# 预留，供二次开发扩展
  qq char(15) NOT NULL DEFAULT '' COMMENT 'QQ',			# 预留，供二次开发扩展，可以弹出QQ直接聊天
  threads int NOT NULL DEFAULT 0 COMMENT '发帖数',		#
  posts int NOT NULL DEFAULT 0 COMMENT '回帖数',		#
  credits int NOT NULL DEFAULT 0 COMMENT '积分',		# 预留，供二次开发扩展
  golds int NOT NULL DEFAULT 0 COMMENT '金币',		# 预留，虚拟币
  rmbs int NOT NULL DEFAULT 0 COMMENT '人民币',		# 预留，人民币
  create_ip int UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时IP',
  create_date int UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  login_ip int UNSIGNED NOT NULL DEFAULT 0 COMMENT '登录时IP',
  login_date int UNSIGNED NOT NULL DEFAULT 0 COMMENT '登录时间',
  logins int UNSIGNED NOT NULL DEFAULT 0 COMMENT '登录次数',
  avatar int UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户最后更新图像时间',
  PRIMARY KEY (uid),
  UNIQUE KEY username (username),
  UNIQUE KEY email (email),						# 升级的时候可能为空
  KEY gid (gid)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4;
INSERT INTO `bbs_user` SET uid=1, gid=1, email='admin@admin.com', username='admin',`password`='d98bb50e808918dd45a8d92feafc4fa3',salt='123456';

# 用户组
DROP TABLE IF EXISTS `bbs_group`;
CREATE TABLE `bbs_group` (
  gid smallint UNSIGNED NOT NULL,			#	
  name char(20) NOT NULL default '',			# 用户组名称
  creditsfrom int NOT NULL default 0,		# 积分从
  creditsto int NOT NULL default 0,		# 积分到
  allowread int NOT NULL default 0,		# 允许访问
  allowthread int NOT NULL default 0,		# 允许发主题
  allowpost int NOT NULL default 0,		# 允许回帖
  allowattach int NOT NULL default 0,		# 允许上传文件
  allowdown int NOT NULL default 0,		# 允许下载文件
  allowtop int NOT NULL default 0,		# 允许置顶
  allowupdate int NOT NULL default 0,		# 允许编辑
  allowdelete int NOT NULL default 0,		# 允许删除
  allowmove int NOT NULL default 0,		# 允许移动
  allowbanuser int NOT NULL default 0,		# 允许禁止用户
  allowdeleteuser int NOT NULL default 0,		# 允许删除用户
  allowviewip int UNSIGNED NOT NULL default 0,	# 允许查看用户敏感信息
  PRIMARY KEY (gid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `bbs_group` SET gid=0, name="游客组", creditsfrom=0, creditsto=0, allowread='1', allowthread=0, allowpost='1', allowattach=0, allowdown='1', allowtop=0, allowupdate=0, allowdelete=0, allowmove=0, allowbanuser=0, allowdeleteuser=0, allowviewip=0;

INSERT INTO `bbs_group` SET gid='1', name="管理员组", creditsfrom=0, creditsto=0, allowread='1', allowthread='1', allowpost='1', allowattach='1', allowdown='1', allowtop='1', allowupdate='1', allowdelete='1', allowmove='1', allowbanuser='1', allowdeleteuser='1', allowviewip='1';
INSERT INTO `bbs_group` SET gid='2', name="超级版主组", creditsfrom=0, creditsto=0, allowread='1', allowthread='1', allowpost='1', allowattach='1', allowdown='1', allowtop='1', allowupdate='1', allowdelete='1', allowmove='1', allowbanuser='1', allowdeleteuser='1', allowviewip='1';
INSERT INTO `bbs_group` SET gid='4', name="版主组", creditsfrom=0, creditsto=0, allowread='1', allowthread='1', allowpost='1', allowattach='1', allowdown='1', allowtop='1', allowupdate='1', allowdelete='1', allowmove='1', allowbanuser='1', allowdeleteuser=0, allowviewip='1';
INSERT INTO `bbs_group` SET gid='5', name="实习版主组", creditsfrom=0, creditsto=0, allowread='1', allowthread='1', allowpost='1', allowattach='1', allowdown='1', allowtop='1', allowupdate='1', allowdelete=0, allowmove='1', allowbanuser=0, allowdeleteuser=0, allowviewip=0;

INSERT INTO `bbs_group` SET gid='6', name="待验证用户组", creditsfrom=0, creditsto=0, allowread='1', allowthread=0, allowpost='1', allowattach=0, allowdown='1', allowtop=0, allowupdate=0, allowdelete=0, allowmove=0, allowbanuser=0, allowdeleteuser=0, allowviewip=0;
INSERT INTO `bbs_group` SET gid='7', name="禁止用户组", creditsfrom=0, creditsto=0, allowread=0, allowthread=0, allowpost=0, allowattach=0, allowdown=0, allowtop=0, allowupdate=0, allowdelete=0, allowmove=0, allowbanuser=0, allowdeleteuser=0, allowviewip=0;

INSERT INTO `bbs_group` SET gid='101', name="一级用户组", creditsfrom=0, creditsto='50', allowread='1', allowthread='1', allowpost='1', allowattach='1', allowdown='1', allowtop=0, allowupdate=0, allowdelete=0, allowmove=0, allowbanuser=0, allowdeleteuser=0, allowviewip=0;
INSERT INTO `bbs_group` SET gid='102', name="二级用户组", creditsfrom='50', creditsto='200', allowread='1', allowthread='1', allowpost='1', allowattach='1', allowdown='1', allowtop=0, allowupdate=0, allowdelete=0, allowmove=0, allowbanuser=0, allowdeleteuser=0, allowviewip=0;
INSERT INTO `bbs_group` SET gid='103', name="三级用户组", creditsfrom='200', creditsto='1000', allowread='1', allowthread='1', allowpost='1', allowattach='1', allowdown='1', allowtop=0, allowupdate=0, allowdelete=0, allowmove=0, allowbanuser=0, allowdeleteuser=0, allowviewip=0;
INSERT INTO `bbs_group` SET gid='104', name="四级用户组", creditsfrom='1000', creditsto='10000', allowread='1', allowthread='1', allowpost='1', allowattach='1', allowdown='1', allowtop=0, allowupdate=0, allowdelete=0, allowmove=0, allowbanuser=0, allowdeleteuser=0, allowviewip=0;
INSERT INTO `bbs_group` SET gid='105', name="五级用户组", creditsfrom='10000', creditsto='10000000', allowread='1', allowthread='1', allowpost='1', allowattach='1', allowdown='1', allowtop=0, allowupdate=0, allowdelete=0, allowmove=0, allowbanuser=0, allowdeleteuser=0, allowviewip=0;

# 板块表，一级, runtime 中存放 forumlist 格式化以后的数据。
DROP TABLE IF EXISTS
    `bbs_forum`;
CREATE TABLE `bbs_forum`(
    `fid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` CHAR(16) NOT NULL DEFAULT '' COMMENT '版块名称',
    `fup` INT UNSIGNED NOT NULL DEFAULT '0' COMMENT '上一级版块，二级版块作为插件',
    `rank` TINYINT UNSIGNED NOT NULL DEFAULT '0' COMMENT '显示，倒序，数字越大越靠前',
    `threads` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0' COMMENT '主题数',
    `todayposts` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
    `todaythreads` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
    `brief` TEXT NULL COMMENT '版块简介 允许HTML',
    `announcement` TEXT NULL COMMENT '版块公告 允许HTML',
    `accesson` INT UNSIGNED NOT NULL DEFAULT '0' COMMENT '是否开启权限控制',
    `orderby` TINYINT NOT NULL DEFAULT '0' COMMENT '默认列表排序，0: 顶贴时间 last_date， 1: 发帖时间 tid',
    `create_date` INT UNSIGNED NOT NULL DEFAULT '0' COMMENT '板块创建时间',
    `icon` INT UNSIGNED NOT NULL DEFAULT '0' COMMENT '板块是否有 icon，存放最后更新时间',
    `moduids` CHAR(120) NOT NULL DEFAULT '' COMMENT '每个版块有多个版主，最多10个： 10*12 = 120，删除用户的时候，如果是版主，则调整后再删除。逗号分隔',
    `seo_title` CHAR(64) NOT NULL DEFAULT '' COMMENT 'SEO 标题，如果设置会代替版块名称',
    `seo_keywords` CHAR(64) NOT NULL DEFAULT '' COMMENT 'SEO keyword',
    PRIMARY KEY(`fid`)
) ENGINE = MyISAM DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
INSERT INTO bbs_forum SET fid='1', name='默认版块', brief='默认版块介绍';
#  cache_date int NOT NULL default 0,		# 最后 threadlist 缓存的时间，6种排序前10页结果缓存。如果是前10页，先读缓存，并依据此字段过期。更新条件：发贴
  
# 版块访问规则, forum.accesson 开启时生效, 记录行数： fid * gid
DROP TABLE IF EXISTS bbs_forum_access;
CREATE TABLE bbs_forum_access (				# 字段中文名
  fid int UNSIGNED NOT NULL default 0,		# fid
  gid int UNSIGNED NOT NULL default 0,		# fid
  allowread TINYINT UNSIGNED NOT NULL default 0,	# 允许查看
  allowthread TINYINT UNSIGNED NOT NULL default 0,	# 允许发主题
  allowpost TINYINT UNSIGNED NOT NULL default 0,	# 允许回复
  allowattach TINYINT UNSIGNED NOT NULL default 0,	# 允许上传附件
  allowdown TINYINT UNSIGNED NOT NULL default 0,	# 允许下载附件
  PRIMARY KEY (fid, gid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

# 论坛主题
DROP TABLE IF EXISTS bbs_thread;
CREATE TABLE bbs_thread (
  fid smallint NOT NULL default 0,			# 版块 id
  tid int UNSIGNED NOT NULL auto_increment,		# 主题id
  top TINYINT NOT NULL default 0,			# 置顶级别: 0: 普通主题, 1-3 置顶的顺序
  uid int UNSIGNED NOT NULL default 0,		# 用户id
  userip int UNSIGNED NOT NULL default 0,		# 发帖时用户ip ip2long()，主要用来清理
  subject char(128) NOT NULL default '',		# 主题
  create_date int UNSIGNED NOT NULL default 0,	# 发帖时间
  last_date int UNSIGNED NOT NULL default 0,	# 最后回复时间

  views int UNSIGNED NOT NULL default 0,		# 查看次数, 剥离出去，单独的服务，避免 cache 失效
  posts int UNSIGNED NOT NULL default 0,		# 回帖数
  images TINYINT NOT NULL default 0,		# 附件中包含的图片数
  files TINYINT NOT NULL default 0,		# 附件中包含的文件数
  mods TINYINT NOT NULL default 0,			# 预留：版主操作次数，如果 > 0, 则查询 modlog，显示斑竹的评分
  closed TINYINT UNSIGNED NOT NULL default 0,	# 预留：是否关闭，关闭以后不能再回帖、编辑。
  firstpid int UNSIGNED NOT NULL default 0,	# 首贴 pid
  lastuid int UNSIGNED NOT NULL default 0,	# 最近参与的 uid
  lastpid int UNSIGNED NOT NULL default 0,	# 最后回复的 pid
  PRIMARY KEY (tid),					# 主键
  KEY (lastpid),					# 最后回复排序
  KEY (fid, tid),					# 发帖时间排序，正序。数据量大时可以考虑建立小表，对小表进行分区优化，只有数据量达到千万级以上时才需要。
  KEY (fid, lastpid)					# 顶贴时间排序，倒序
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

# 置顶主题
DROP TABLE IF EXISTS bbs_thread_top;
CREATE TABLE bbs_thread_top (
  fid smallint NOT NULL default 0,			# 查找板块置顶
  tid int UNSIGNED NOT NULL default 0,		# tid
  top int UNSIGNED NOT NULL default 0,		# top: 0 是普通最新贴，> 0 置顶贴。
  PRIMARY KEY (tid),					#
  KEY (top, tid),					# 最新贴：top=0 order by tid desc / 全局置顶： top=3
  KEY (fid, top)					# 版块置顶的贴 fid=1 and top=1
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

# 论坛帖子数据
DROP TABLE IF EXISTS bbs_post;
CREATE TABLE bbs_post (
  tid int UNSIGNED NOT NULL default 0,		# 主题id
  pid int UNSIGNED NOT NULL auto_increment,		# 帖子id
  uid int UNSIGNED NOT NULL default 0,		# 用户id
  isfirst int UNSIGNED NOT NULL default 0,	# 是否为首帖，与 thread.firstpid 呼应
  create_date int UNSIGNED NOT NULL default 0,	# 发贴时间
  userip int UNSIGNED NOT NULL default 0,		# 发帖时用户ip ip2long()
  images smallint NOT NULL default 0,		# 附件中包含的图片数
  files smallint NOT NULL default 0,		# 附件中包含的文件数
  doctype TINYINT NOT NULL default 0,		# 类型，0: html, 1: txt; 2: markdown; 3: ubb
  quotepid int NOT NULL default 0,		# 引用哪个 pid，可能不存在

  message longtext NOT NULL,				# 内容，用户提示的原始数据
  message_fmt longtext NOT NULL,			# 内容，存放的过滤后的html内容，可以定期清理，减肥。
  PRIMARY KEY (pid),
  KEY (tid, pid),
  KEY (uid)						# 我的回帖，清理数据需要
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
# 编辑历史

#论坛附件表  只能按照从上往下的方式查找和删除！ 此表如果大，可以考虑通过 aid 分区。
DROP TABLE IF EXISTS bbs_attach;
CREATE TABLE bbs_attach (
  aid int UNSIGNED NOT NULL auto_increment ,	# 附件id
  tid int NOT NULL default 0,			# 主题id
  pid int NOT NULL default 0,			# 帖子id
  uid int NOT NULL default 0,			# 用户id
  filesize int UNSIGNED NOT NULL default 0,	# 文件尺寸，单位字节
  width mediumint UNSIGNED NOT NULL default 0,	# width > 0 则为图片
  height mediumint UNSIGNED NOT NULL default 0,	# height
  filename char(120) NOT NULL default '',		# 文件名称，会过滤，并且截断，保存后的文件名，不包含URL前缀 upload_url
  orgfilename char(120) NOT NULL default '',		# 上传的原文件名
  filetype char(7) NOT NULL default '',			# 文件类型: image/txt/zip，小图标显示 <i class="icon filetype image"></i>
  create_date int UNSIGNED NOT NULL default 0,	# 文件上传时间 UNIX 时间戳
  comment char(100) NOT NULL default '',		# 文件注释 方便于搜索
  downloads int NOT NULL default 0,		# 下载次数，预留
  credits int NOT NULL default 0,			# 需要的积分，预留
  golds int NOT NULL default 0,			# 需要的金币，预留
  rmbs int NOT NULL default 0,			# 需要的人民币，预留
  isimage TINYINT NOT NULL default 0,		# 是否为图片
  PRIMARY KEY (aid),					# aid
  KEY pid (pid),					# 每个帖子下多个附件
  KEY uid (uid)						# 我的附件，清理数据需要。
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

# 我的主题，每个主题不管回复多少次，只记录一次。大表，需要分区。
DROP TABLE IF EXISTS bbs_mythread;
CREATE TABLE bbs_mythread (
  uid int UNSIGNED NOT NULL default 0,		# uid
  tid int UNSIGNED NOT NULL default 0,		# 用来清理，删除板块的时候需要
  PRIMARY KEY (uid, tid)				# 每一个帖子只能插入一次 unique
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

# 我的回帖。大表，需要分区。
DROP TABLE IF EXISTS bbs_mypost;
CREATE TABLE bbs_mypost (
  uid int UNSIGNED NOT NULL default 0,		# uid
  tid int UNSIGNED NOT NULL default 0,		# 用来清理
  pid int UNSIGNED NOT NULL default 0,		#
  KEY (tid),						#
  PRIMARY KEY (uid, pid)				#
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

# session 表
# 缓存到 runtime 表。 online_0 全局 online_fid 版块。提高遍历效率。
DROP TABLE IF EXISTS bbs_session;
CREATE TABLE bbs_session (
  sid char(32) NOT NULL default 0,			# 随机生成 id 不能重复 uniqueid() 13 位
  uid int UNSIGNED NOT NULL default 0,		# 用户id 未登录为 0，可以重复
  fid TINYINT UNSIGNED NOT NULL default 0,		# 所在的版块
  url char(32) NOT NULL default '',			# 当前访问 url
  ip int UNSIGNED NOT NULL default 0,		# 用户ip
  useragent char(128) NOT NULL default '',		# 用户浏览器信息
  data char(255) NOT NULL default '',			# session 数据，超大数据存入大表。
  bigdata TINYINT NOT NULL default 0,		# 是否有大数据。
  last_date int UNSIGNED NOT NULL default 0,	# 上次活动时间
  PRIMARY KEY (sid),
  KEY ip (ip),
  KEY fid (fid),
  KEY uid_last_date (uid, last_date)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS bbs_session_data;
CREATE TABLE bbs_session_data (
  sid char(32) NOT NULL default 0,			#
  last_date int UNSIGNED NOT NULL default 0,	# 上次活动时间
  data text NOT NULL,					# 存超大数据
  PRIMARY KEY (sid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

# 版主操作日志
DROP TABLE IF EXISTS bbs_modlog;
CREATE TABLE bbs_modlog (
  logid int UNSIGNED NOT NULL auto_increment,	# logid
  uid int UNSIGNED NOT NULL default 0,		# 版主 uid
  tid int UNSIGNED NOT NULL default 0,		# 主题id
  pid int UNSIGNED NOT NULL default 0,		# 帖子id
  subject char(32) NOT NULL default '',			# 主题
  comment char(64) NOT NULL default '',			# 版主评价
  rmbs int NOT NULL default 0,			# 加减人民币, 预留
  create_date int UNSIGNED NOT NULL default 0,	# 时间
  action char(16) NOT NULL default '',			# top|delete|untop
  PRIMARY KEY (logid),
  KEY (uid, logid),
  KEY (tid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
# 持久的 key value 数据存储, ttserver, mysql
DROP TABLE IF EXISTS bbs_kv;
CREATE TABLE bbs_kv (
  k char(32) NOT NULL default '',
  v mediumtext NOT NULL,
  expiry int UNSIGNED NOT NULL default 0,		# 过期时间
  PRIMARY KEY(k)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

# 缓存表，用来保存临时数据。
DROP TABLE IF EXISTS bbs_cache;
CREATE TABLE bbs_cache (
  k char(32) NOT NULL default '',
  v mediumtext NOT NULL,
  expiry int UNSIGNED NOT NULL default 0,		# 过期时间
  PRIMARY KEY(k)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

# 临时队列，用来保存临时数据。
DROP TABLE IF EXISTS bbs_queue;
CREATE TABLE bbs_queue (
  queueid int UNSIGNED NOT NULL default 0,		# 队列 id
  v int NOT NULL default 0,			# 队列中存放的数据，只能为 int
  expiry int UNSIGNED NOT NULL default 0,		# 过期时间，默认 0，不过期
  UNIQUE KEY(queueid, v),
  KEY(expiry)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


# 系统表, id
# MAXID 表，几个主要的大表，每天的最大ID，用来削减索引 create_date
# day = 0 表示月； month = 0 AND day = 0 表示年
# 计划任务，1点执行。 不需要太精准，用来作为过滤条件。
# 可以有效的过滤冷热数据
DROP TABLE IF EXISTS `bbs_table_day`;
CREATE TABLE `bbs_table_day` (
  `year` smallint UNSIGNED NOT NULL DEFAULT 0 COMMENT '年',	#
  `month` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '月', 	#
  `day` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '日', 		#
  `create_date` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间戳', 	#
  `table` char(16) NOT NULL default '' COMMENT '表名',			#
  `maxid` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '最大ID', 	#
  `count` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '总数', 		#
  PRIMARY KEY (`year`, `month`, `day`, `table`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

