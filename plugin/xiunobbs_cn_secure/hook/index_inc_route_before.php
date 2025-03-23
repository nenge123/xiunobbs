<?php exit;
/**
 * 修罗登录保护 校验数据
 * 校验账号在线及账号安全情况
 * xiunobbs_cn
 * @create 2022-10-07
 * @author 浅唱兔君
 */
	
	$token = param('bbs_token', '');
	$action = param(0);
	if(isset($user['secure_token'])){ 
	    if(strrpos($user['secure_token'],$token) !== false){
	    }else{
		    $uid = 0;
		    $_SESSION['uid'] = $uid;
		    user_token_clear();
		    if(strrpos($_SERVER['SERVER_NAME'].$_SERVER["REQUEST_URI"],'/admin') !== false){
		        message(0, jump('请重新登录账号！', '../'.url('user-login'), 100));
		    }else{
		        message(0, jump('您的登录状态已失效！', url('user-login'), 100));
		    }
	    }
	}
	if(isset($user['secure_token'])){ 
	    if($user['pwd_risk'] == 1 || $user['check'] == 1 ){
	        if($action == 'secure' || param(1) == 'logout' || param(1) == 'resetpw') {
	        }else{
	            if($user['security_token'] <> $user['secure_token']){
	                if(strrpos($_SERVER['SERVER_NAME'].$_SERVER["REQUEST_URI"],'/admin') !== false){
		                header("Location: /".url('secure'));
                        exit();
		            }else{
		                header("Location: ".url('secure'));
                        exit();
		            }
	            }
	        }
	    }
	}
?>