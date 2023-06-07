<?php
defined('XIUNO')||die('return to <a href="">Home</a>');
if(empty($myapp->router->act)){
    $myapp->router->act = 'in';
}
#if(!empty($myapp->data['tokens']))print_r($myapp->data['tokens']);
#if(!empty($myapp->data['user']))print_r($myapp->data['user']);
#if(!empty($_SESSION))print_r($_SESSION);
if($myapp->data['method'] == 'POST'){
    $password = $myapp->POST('password');
    $username = $myapp->POST('username');
    $email = $myapp->POST('email');
    if($myapp->router->act=='in'){
        
        #检查密码
        $passtxt = false;
        if(empty($password))$passtxt = $language['please_input_password'];#请填写密码
        elseif(strlen($password)!=32)$passtxt = $language['invalid_password'];#密码必须前端MD5后32位 且不会写入数据库 因此不需要效验密码是否非法
        elseif(!preg_match('/^[\d\w]+$/',$password))$passtxt = $language['invalid_password'];#重复密码不对等
        elseif(in_array($password,array('d41d8cd98f00b204e9800998ecf8427e','7215ee9c7d9dc229d2921a40e899ec5f')))$passtxt = $language['password_is_empty'];#空值password_is_empty
        if($passtxt)$myapp->json(array('code'=>-1,'key'=>'password','msg'=>$passtxt));

        
        #检查EMAIL
        $emailtxt = false;
        $isEmail = true;
        if(empty($email)) $emailtxt = $language['please_input_email'];#请填写邮箱
        elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){
            $isEmail = false;            
            if(mb_strlen($email)<3||mb_strlen ($email)>10) $emailtxt = $language['please_input_username_size'];#检查长度
            elseif(preg_match('/[\<\>\?\:\&\*\%\#\$\@]+/',$email))$emailtxt = $language['invalid_input_username_char'];#过滤特殊字符 防止XSS
        }elseif(mb_strlen($email)>40) $emailtxt = $language->lang('email_too_long',mb_strlen($email));
        if($emailtxt)$myapp->json(array('code'=>-1,'key'=>'email','msg'=>$emailtxt));

        if($isEmail){
            $user = $myapp->DB('FetchRow','user',array('email'=>$email));
        }else{
            $user = $myapp->DB('FetchRow','user',array('username'=>$email));
        }
        if(empty($user)){
            $myapp->json(array('code'=>-1,'key'=>'email','msg'=>$language['username_not_exists']));
        }else{
            $HashPassword = !empty($user['salt'])&&strlen($user['password'])==32 ? md5($password.$user['salt'])==$user['password']:$myapp->Verify($password,$user['password']);
            if($HashPassword){
                $myapp->login($user,$password,$myapp->POST('argee_cookies'));
                $myapp->json(array('code'=>0,'msg'=>$language['login_successfully'],'href'=>$myapp->site->root));
            }
        }
        $myapp->json(array('code'=>-1,'key'=>'password','msg'=>$language['password_incorrect']));
    }elseif($myapp->router->act=='up'){
        #检查密码
        $passtxt = false;
        if(empty($password))$passtxt = $language['please_input_password'];#请填写密码
        elseif(strlen($password)!=32)$passtxt = $language['invalid_password'];#密码必须前端MD5后32位 且不会写入数据库 因此不需要效验密码是否非法
        elseif($password != $myapp->POST('new-password'))$passtxt = $language['repeat_password_incorrect'];#重复密码不对等
        elseif(!preg_match('/^[\d\w]+$/',$password))$passtxt = $language['invalid_password'];#重复密码不对等
        elseif(in_array($password,array('d41d8cd98f00b204e9800998ecf8427e','7215ee9c7d9dc229d2921a40e899ec5f')))$passtxt = $language['password_is_empty'];#空值
        if($passtxt)$myapp->json(array('code'=>-1,'key'=>'password','msg'=>$passtxt));

        #检查EMAIL
        $emailtxt = false;
        if(empty($email)) $emailtxt = $language['please_input_email'];#请填写邮箱
        elseif(!filter_var($email,FILTER_VALIDATE_EMAIL))$emailtxt = $language['invalid_email'];#非法邮箱 如需限定则用正则限制后缀
        elseif(mb_strlen($email)>40) $emailtxt = $language->lang('email_too_long',mb_strlen($email)); #邮箱过长
        if($emailtxt)$myapp->json(array('code'=>-1,'key'=>'email','msg'=>$emailtxt));

        #检查用户名
        $usernametxt = false;
        if(empty($username))$usernametxt = $language['please_input_username']; #请填写用户名
        elseif(mb_strlen($username)<3||mb_strlen ($username)>10) $usernametxt = $language['please_input_username_size'];#检查长度 2-10
        elseif(preg_match('/[\<\>\?\:\&\*\%\#\$\@]+/',$username))$usernametxt = $language['invalid_input_username_char'];#过滤特殊字符 防止XSS
        if($usernametxt)$myapp->json(array('code'=>-1,'key'=>'username','msg'=>$usernametxt));

        #邮箱验证 确保已配置
        if(!empty($myapp->data['settings']['verify_email'])&&!empty($myapp->data['settings']['smtp'][0])){
            #邮件验证 使用session_id
            if(!session_id()){
                session_start();
            }
            $verify = $myapp->DB('FetchValue','verify','data',array('sid'=>session_id(),'>:date'=>$myapp->data['time']-300));
            if(empty($verify)){
                #开始验证
                $username = $username;
                $ysdm = array("*","+","-");
                $srand = array_rand($ysdm,1);
                $code = mt_rand(50,100).$ysdm[$srand].mt_rand(1,50);#生成随机运算
                $verify = eval("return ".$code.";");
                $code .= " = ? ";
                ob_start();
                include $myapp->getTemplate('phpMailer/sendcode');
                $content = ob_get_contents();
                ob_clean();
                $result = $myapp->F('mail',$email,$language['verify_code'],$content);#code=0为发送成功 -1为失败
                if($result['code']!=-1){
                    #成功发送写入数据库
                    $myapp->DB('Insert','verify',array('sid'=>session_id(),'data'=>$verify,'date'=>$myapp->data['time']),true);
                }
                $result['code']=-1;
                $myapp->json($result);
            }elseif(empty($_POST['code'])){
                #空的验证码
                $myapp->json(array('code'=>-1,'key'=>'code','msg'=>$language['please_input_verify_code']));
            }elseif($verify != $_POST['code']){
                #验证码不对
                $myapp->json(array('code'=>-1,'key'=>'code','msg'=>$language['verify_code_incorrect']));
            }
        }
        #匹配邮件
        $isEmail = true;
        $user = $myapp->DB('FetchRow','user',array('email'=>trim($email)));
        if(empty($user)){
            #匹配用户名
            $isEmail = false;
            $user = $myapp->DB('FetchRow','user',array('username'=>trim($username)));

        }
        if(!empty($user)){
            #兼容旧密码
            $HashPassword = !empty($user['salt'])&&strlen($user['password'])==32 ? md5($password.$user['salt'])==$user['password']:$myapp->Verify($password,$user['password']);
            if($HashPassword){
                #登录 参数为1是因为这人经常忘记已经注册
                #if(session_id()&&!isset($_SESSION['uid'])){
                    $myapp->clearSession();
                #}
                $myapp->login($user,$password,1);
                $myapp->json(array('code'=>0,'msg'=>$language['login_successfully'],'href'=>$myapp->site->root));
            }
            $myapp->json(array('code'=>-1,'key'=>'email','msg'=>$language[$isEmail?'email_is_in_use':'username_is_in_use']));
        }else{
            #登录 参数为1是因为这人经常忘记已经注册
            #if(session_id()&&!isset($_SESSION['uid'])){
                $myapp->clearSession();
            #}
            #注册
            $userdata = array(
                'email'=>$email,
                'username'=>$myapp->POST('username'),
                'password'=>$myapp->Hash($password),
                'create_ip'=>$myapp->data['longip'],
                'login_date'=>$myapp->data['time'],
                'login_ip'=>$myapp->data['longip'],
                'create_date'=>$myapp->data['time'],
                'logins'=>1,
            );
            $result = $myapp->DB('Insert','user',$userdata);
            if($result['lastid']){
                $userdata['uid'] = $result['lastid'];
                $myapp->login($userdata,'',1);
                $myapp->json(array('code'=>0,'msg'=>$language['login_successfully'],'href'=>$myapp->site->root));
            }
            $myapp->json(array('code'=>-1,'msg'=>$language['unknow_error']));
        }
        $myapp->json(array('code'=>-1,'msg'=>$language['unknow_action']));
    }
}
if($myapp->router->act=='in'){
    $myapp->data['title']= $language['login'];
}elseif($myapp->router->act=='up'){
    $myapp->data['title']= $language['user_create'];
}elseif($myapp->router->act=='reset'){
    $myapp->data['title']= $language['user_resetpw'];
}elseif($myapp->router->act=='out'){
    $myapp->data['title']= $language['logout_successfully'];
    $myapp->loginOut();
}else{
    $myapp->data['title']= $language['unknow_action'];
}
include $myapp->getTemplate('sign');