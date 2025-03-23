<?php
/**
 * 修罗登录保护 认证校验
 * 发送验证码和校验验证码
 * xiunobbs_cn
 * @create 2022-10-07
 * @author 浅唱兔君
 */
 
    !defined('DEBUG') AND exit('Access Denied.');
    $action = param(1);
    is_numeric($action) AND $action = '';

    //服务器需支持发邮件，可以找服务器供应商确定，如无法发送可以利用第三方接口或调用其他服务器发信
    //如无法发信或发信成功后报错，请安装STMP修复插件，https://www.xiunobbs.cn/thread-3285.htm

    if($user['uid']=='' || $user['uid']=='0'){
        header("Location: /");
    }
    if($user['secure_token'] == $user['security_token']){
        header("Location: /");
    }
    if($user['check'] == 1 || $user['pwd_risk'] == 1){
        if($method == 'GET') {
            
            if($user['email'] <> '' || $user['email'] <> null ){
                $header['title'] = '安全认证';
    	        include _include(APP_PATH.'plugin/xiunobbs_cn_secure/htm/secure.htm');
            }elseif($conf['user_resetpw_on'] == 1){
                //跳转找回页面
                header('Location:'.url('user-resetpw'));
                
            }else{
                message(-1, lang('resetpw_not_on'));
            }
        }else if($method == 'POST') {
            if($action == 'send_code') {//获取验证码
                $action2 = param(2);
                if($action2 == 'user_secure') {
                    $email = $user['email'];//获取该用户邮箱
    		        $code = rand(100000, 999999);
    		        $_SESSION['user_secureh_email'] = $email;
    		        $_SESSION['user_secureh_code'] = $code;
                }else { 
        	        message(-1, 'error');
    	        }
                $message = lang('send_code_template', array('rand'=>$code, 'sitename'=>$conf['sitename']));
    	        $subject = '安全认证';
    	        $smtplist = include _include(APP_PATH.'conf/smtp.conf.php');
    	        $n = array_rand($smtplist);
    	        $smtp = $smtplist[$n];
    	        $r = MyApp::xn_send_mail($smtp, $conf['sitename'], $email, $subject, $message);
    	        if($r === TRUE) {
    	            user_update($user['uid'], array('attempt'=>0));
    		        message(0, lang('send_successfully'));
    	        }else {
    		        xn_log($errstr, 'send_mail_error');
    		        message(-1, $errstr);
    	        }
            }elseif($action == 'user_secure') {//提交验证
                $email = $user['email'];
                $code = param('code');
                $tablepre = $db->tablepre;
                $sql = "SELECT `attempt` FROM {$tablepre}user WHERE uid = {$user['uid']};";
                $user_attempt = db_sql_find_one($sql);//获取信息
                if($user_attempt['attempt'] >= 5){
                    message('code', '尝试次数过多,请重新获取验证码');
                }
    		    empty($code) AND message('code', lang('please_input_verify_code'));
                $sess_email = _SESSION('user_secureh_email');
    		    $sess_code = _SESSION('user_secureh_code');
    		    empty($sess_code) AND message('code', lang('click_to_get_verify_code'));
    		    empty($sess_email) AND message('code', lang('click_to_get_verify_code'));
    		    user_update($user['uid'], array('attempt+'=>1));
    		    $email != $sess_email AND message('code', lang('verify_code_incorrect'));
    		    $code != $sess_code AND message('code', lang('verify_code_incorrect'));
    		    if($user['pwd_risk'] == 1){
    		        user_update($user['uid'], array('security_token'=>$user['secure_token'],'attempt'=>0));
    		        //本次登录可正常使用，下次登录还需进行邮箱验证
    		    }else{
    		        user_update($user['uid'], array('tried_log'=>0,'check'=>0,'check_date'=>0,'pwd_risk'=>0,'attempt'=>0));
    		        //可能误报，或爆破密码未成功
    		    }
    		    $_SESSION['user_secureh_email'] = '';
    		    $_SESSION['user_secureh_code'] = '';
    		    message(0, '认证通过');
            }
        }
    }else{
        header("Location: /");
    }
?>