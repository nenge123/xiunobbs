<?php exit;
/**
 * 修罗登录保护 重置密码
 * 找回账号方式修改密码，重置异常状态
 * xiunobbs_cn
 * @create 2022-10-07
 * @author 浅唱兔君
 */

    global $_uid;
    db_update('user', array('uid'=>$_uid), array('tried_log'=>0,'check'=>0,'check_date'=>0,'pwd_risk'=>0,'secure_token'=>'','security_token'=>''));
?>