<?php exit;
/**
 * 修罗登录保护 退出登陆
 * 清除数据库中的校验凭证
 * xiunobbs_cn
 * @create 2022-10-07
 * @author 浅唱兔君
 */

    $user_token = $user['secure_token'];
    $user_token = str_replace($token, "", $user_token);
    $user_token = str_replace(',,', ',', $user_token);
    db_update('user', array('uid'=>$uid), array('secure_token'=>$user_token));
?>