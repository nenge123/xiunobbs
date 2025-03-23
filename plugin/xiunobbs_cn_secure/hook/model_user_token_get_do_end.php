<?php exit;
/**
 * 修罗登录保护 校验数据
 * 校验数据中是否保护当前登陆数据
 * xiunobbs_cn
 * @create 2022-10-07
 * @author 浅唱兔君
 */

    $user_secure = user_read($_uid);
    if(strrpos($user['secure_token'],$token) !== false){
    }else{
        return false;
    }
?>