<?php exit;
/**
 * 修罗登录保护 登录成功
 * 判断是否需要进行二次验证
 * xiunobbs_cn
 * @create 2022-10-07
 * @author 浅唱兔君
 */

    $tablepre = $db->tablepre;
    $sql = "SELECT `tried`,`check`,`check_date`,`pwd_risk`,`secure_token` FROM {$tablepre}user WHERE uid = {$_user['uid']};";
    $user_secure = db_sql_find_one($sql);//获取信息
    user_update($_user['uid'], array('tried'=>0));//清空尝试登录次数
    $token_arr = explode(',',$user_secure['secure_token']);
    if($user_secure['check'] == 1){
        //爆破者第二次登录或用户登录账号，第一次爆破未进行邮箱验证
	    user_update($_user['uid'], array('pwd_risk'=>1,'secure_token'=>$token_arr[count($token_arr) - 1],'security_token'=>''));//提示密码可能泄露要求改密，该UID其他设备全部强制下线
    }else{
	    if($user_secure['tried']>=10){
		    user_update($_user['uid'], array('tried_log'=>$user_secure['tried'],'check'=>1,'check_date'=>time(),'secure_token'=>$token_arr[count($token_arr) - 1],'security_token'=>''));//强制验证邮箱，记录首次爆破该UID其他设备全部强制下线
	    }
    }
?>