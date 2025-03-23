<?php exit;
/**
 * 修罗登录保护 登录操作
 * 记录尝试登录次数
 * xiunobbs_cn
 * @create 2022-10-07
 * @author 浅唱兔君
 */

    user_update($_user['uid'], array('tried+'=>1));
?>