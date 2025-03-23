<?php
/**
 * 修罗登录保护 卸载程序
 * 删除安装时创建的字段，还原数据库
 * xiunobbs_cn
 * @create 2022-10-07
 * @author 浅唱兔君
 */
 
    !defined('DEBUG') AND exit('Forbidden');
    $tablepre = $db->tablepre;
    $sql = "ALTER TABLE {$tablepre}user DROP COLUMN `tried`;";
    db_exec($sql);
    $sql = "ALTER TABLE {$tablepre}user DROP COLUMN `tried_log`;";
    db_exec($sql);
    $sql = "ALTER TABLE {$tablepre}user DROP COLUMN `check`;";
    db_exec($sql);
    $sql = "ALTER TABLE {$tablepre}user DROP COLUMN `check_date`;";
    db_exec($sql);
    $sql = "ALTER TABLE {$tablepre}user DROP COLUMN `pwd_risk`;";
    db_exec($sql);
    $sql = "ALTER TABLE {$tablepre}user DROP COLUMN `secure_token`;";
    db_exec($sql);
    $sql = "ALTER TABLE {$tablepre}user DROP COLUMN `security_token`;";
    db_exec($sql);
    $sql = "ALTER TABLE {$tablepre}user DROP COLUMN `attempt`;";
    db_exec($sql);
?>