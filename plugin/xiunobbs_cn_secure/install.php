<?php
/**
 * 修罗登录保护 安装程序
 * 插件安装创建一些需要的数据库字段
 * xiunobbs_cn
 * @create 2022-10-07
 * @author 浅唱兔君
 */

    !defined('DEBUG') AND exit('Forbidden');
    $tablepre = $db->tablepre;
    $sql = "ALTER TABLE {$tablepre}user ADD COLUMN `tried` INT(11) DEFAULT '0' COMMENT '尝试登录次数';";
    db_exec($sql);//每次尝试登录+1
    $sql = "ALTER TABLE {$tablepre}user ADD COLUMN `tried_log` INT(11) DEFAULT '0' COMMENT '爆破登录记录';";
    db_exec($sql);
    $sql = "ALTER TABLE {$tablepre}user ADD COLUMN `check` INT(3) DEFAULT '0' COMMENT '触发信息验证';";
    db_exec($sql);//登录成功后判断尝试登录次数大于等于10触发信息验证，并重置尝试登录次数为0，触发验证时间为空时记录，可能是爆破成功时间
    $sql = "ALTER TABLE {$tablepre}user ADD COLUMN `check_date` INT(11) DEFAULT '0' COMMENT '触发验证时间';";
    db_exec($sql);
    $sql = "ALTER TABLE {$tablepre}user ADD COLUMN `pwd_risk` INT(3) DEFAULT '0' COMMENT '密码泄露提示';";
    db_exec($sql);//修改密码或找回密码后重置为0.并且将触发验证时间&触发信息验证也重置为0
    $sql = "ALTER TABLE {$tablepre}user ADD COLUMN `secure_token` longtext NOT NULL default '' COMMENT '用户登录授权';";
    db_exec($sql);
    $sql = "ALTER TABLE {$tablepre}user ADD COLUMN `security_token` longtext NOT NULL default '' COMMENT '安全用户授权';";
    db_exec($sql);
    $sql = "ALTER TABLE {$tablepre}user ADD COLUMN `attempt` INT(3) DEFAULT '0' COMMENT '验证码尝试次';";
    db_exec($sql);
?>