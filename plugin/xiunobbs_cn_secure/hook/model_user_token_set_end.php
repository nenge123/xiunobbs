<?php exit;
/**
 * 修罗登录保护 登陆完成
 * 将校验凭证写入数据库
 * xiunobbs_cn
 * @create 2022-10-07
 * @author 浅唱兔君
 */

    global $db;
    $tablepre = $db->tablepre;
    $sql = "SELECT `secure_token` FROM {$tablepre}user WHERE uid = {$uid};";
    $user_secure = db_sql_find_one($sql);
    if($user_secure['secure_token'] == ''){
	    $user_token = $token;
    }else{
        $token_arr = explode(',',$user_secure['secure_token']);
        array_push($token_arr,$token);
	    $token_arr=array_slice($token_arr,-10);
	    $user_token=implode(',',$token_arr);
    }
    db_update('user', array('uid'=>$uid), array('secure_token'=>$user_token));
?>