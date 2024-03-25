<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * AJAX API 登录
 * 推荐使用AJAX途径调用API,因为这样可以避免辣鸡黑客遍历网站.
 * javascript 网页任意位置调用此API
 * await N.ajax({href:'',api:'login',ajax:true});
 */
defined('WEBROOT') or die('return to <a href="">Home</a>');
if($myapp->data['user']['uid']>0):
    #已登录
    $myapp->data['msgtitle'] = $myapp->getLang('member_login');
    $myapp->data['msgcontent'] = $myapp->getLang('member_is_login_msg');
    include $myapp->template('member/modal-msg');
    $myapp->exit();
endif;
$username = input_post('username',false);
$password = input_post('password',false);
$loginhash = input_post('hash',false);
$logintime = intval(input_post('time',0));
$myapp->data['loginhash'] = $myapp->get_time_hash('login');
if($myapp->data['method']=='POST'):
    $result = array(
        'type'  => 'feedback',
        'value'=>array(
            'time'=>$myapp->data['time'],
            'hash'=>$myapp->data['loginhash'],
        )
    );
    #$result['success'] = $myapp->getLang('member_login_ok_msg');
    #$myapp->json($result);
    if($myapp->get_time_decode($loginhash,$logintime)!='login'):
        #非法请求 验证不通过
        $result['alert'] = $myapp->getLang('member_hash_not_vaild');
        $myapp->json($result);
    endif;
    if($myapp->data['time'] - $logintime < 2):
        #非法请求 操作过快 秒男级,很可疑!
        $result['alert'] = $myapp->getLang('member_login_too_fast');
        $myapp->json($result);
    endif;
    if(empty($username)):
        $result['valid'] = array(
            'username'=>$myapp->getLang('member_account_msg'),
            'password' =>false,
        );
        $myapp->json($result);
    endif;
    if(empty($password)||strpos($password,' ')!==false||!preg_match('/^[\x21-\x7e]+$/',$password)):
        $result['valid'] = array(
            'username'=>true,
            'password' =>$myapp->getLang('member_password_msg'),
        );
        $myapp->json($result);
    endif;
    #查询数据库
    $queryname = 'username';
    if(filter_var($username, FILTER_VALIDATE_EMAIL)):
        $queryname = 'email';
    endif;
    $checkuser = $myapp->t('user')->query(' where `'.$queryname.'` = ? ',[$username]);
    if(empty($checkuser)):
        $result['valid'] = array(
            'username'=>$myapp->getLang('member_account_invail'),
            'password' =>true,
        );
        $myapp->json($result);
    endif;
    #兼容旧有盐密码模式
    if(strlen($checkuser['password'])==32):
        if(md5($password.$_user['salt']) != $checkuser['password']):
            $result['valid'] = array(
                'username'=>true,
                'password' =>$myapp->getLang('member_password_invail'),
            );
            $myapp->json($result);
        endif;
    elseif(!$myapp->pwinvalid($password,$checkuser['password'])):
        #新密码验证 aes效验
        $result['valid'] = array(
            'username'=>true,
            'password' =>$myapp->getLang('member_password_invail'),
        );
    endif;
    #验证成功
    #更新密码和登录次数
    $update = array(
        'password'=>$myapp->pwcode($password),
        'login_ip'=>ip2str($myapp->data['ip']),
        'login_date'=>$myapp->data['time'],
        'logins'=>$checkuser['logins']+1
    );
    $myapp->t('user')->update2json($update,'uid',$checkuser['uid']);
    if(input_post('cookies')):
        $myapp->setCookies('login',$myapp->encrypt($checkuser['uid'].':'.$myapp->data['time']));
    else:
        if(!isset($_SESSION))session_start();
        $_SESSION['login_uid'] = $checkuser['uid'];
    endif;
    $result['success'] = $myapp->getLang('member_login_ok_msg');
    $myapp->json($result);
endif;
include $myapp->template('member/modal-login');
$myapp->exit();