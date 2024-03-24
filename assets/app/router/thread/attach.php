<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 导肮 主题 分支操作
 * 下载附件
 * 扣分规则仍需要细致处理
 */

defined('WEBROOT') or die('return to <a href="">Home</a>');
if(empty($thread)):
    #没有阅读权限 阻止意外加载
    $myapp->data['errortitle'] = $myapp->getLang('thread_unable_read');
    $myapp->data['errormessage'] = $myapp->getLang('thread_unable_read_message');
    include $myapp->template('thread/error');
    $myapp->exit();
endif;
#附件下载
if(empty($access['allowdown'])):
    #无权下载附件
    $myapp->data['errortitle'] = $myapp->getLang('thread_unable_down');
    $myapp->data['errormessage'] = $myapp->getLang('thread_unable_down_message');
    include $myapp->template('thread/error');
    $myapp->exit();
endif;
$aid = router_value(3);
if(empty($aid) || !is_numeric($aid)):
    #参数异常
    $myapp->data['errortitle'] = $myapp->getLang('error_input');
    $myapp->data['errormessage'] = $myapp->getLang('error_input_message');
    include $myapp->template('thread/error');
    $myapp->exit();
endif;
$attach = $myapp->t('attach')->value($aid);
if(empty($attach)):
    #数据异常
    $myapp->data['errortitle'] = $myapp->getLang('thread_no_attach');
    $myapp->data['errormessage'] = $myapp->getLang('thread_no_attach_message');
    include $myapp->template('thread/error');
    $myapp->exit();
endif;
$attachfile = $myapp->get_dir_path($myapp->data['path']['attach'].$attach['filename']);
$attachfile = $myapp->data['path']['images'].'logo.png';
if(!is_file($attachfile)):
    #附件不存在
    $myapp->data['errortitle'] = $myapp->getLang('thread_null_attach');
    $myapp->data['errormessage'] = $myapp->getLang('thread_null_attach_message');
    include $myapp->template('thread/error');
    $myapp->exit();
endif;
if($myapp->data['method'] == 'GET'):
    #GET状态 检测
    $downloadhref = $myapp->url($threadhref.'aid-'.$attach['aid']);
    #加密向量 $myapp->data['time']
    #$downloadiv = $myapp->ivcrypt($myapp->data['time']);
    #加密验证
    #$myapp->data['downloadhash'] = bin2hex($myapp->encrypt($attach['aid'],$downloadiv));
    $myapp->data['allowdown'] = true;
    if(!$myapp->plugin_read('thread_attach_cost',$attach,$thread)):
        #付费拦截
        if(!empty($attach['credits']) || !empty($attach['golds']) || !empty($attach['rmbs'])):
            #附件付费
            $myapp->data['errortitle'] = $myapp->getLang('thread_attach_cost');
            foreach(['credits','golds','rmbs'] as $v):
                if($attach[$v]>0):
                    $myapp->data['errormessage'][$v] = $attach[$v];
                endif;
            endforeach;
        endif;
        #默认付费
        if(!empty($myapp->data['settings']['thread_attach_cost'])):
            $myapp->data['errortitle'] = $myapp->getLang('thread_attach_cost');
            foreach($myapp->data['settings']['thread_attach_cost'] as $key=>$v):
                if($v>0):
                    if(empty($myapp->data['errormessage'][$key])) $myapp->data['errormessage'][$key] = $v;
                    else $myapp->data['errormessage'][$key] += $v;
                endif;
            endforeach;
        endif;
        if(!empty($myapp->data['errormessage'])):
            if(!$myapp->data['user']['uid']):
                #游客 切换登录节目
                #include $myapp->template('member/login');
                #$myapp->exit();
            endif;
            foreach($myapp->data['errormessage'] as $key=>$value):
                #$myapp->data['user'][$key]
                #$myapp->data['user'][$key] = 0;
                #没有积分 或者 积分为负数
                if(empty($myapp->data['user'][$key]) || $value>$myapp->data['user'][$key]):
                    $myapp->data['allowdown'] = false;
                endif;
            endforeach;
            include $myapp->template('thread/download');
            $myapp->exit();
        endif;
    endif;
    #可以下载
    $myapp->data['errortitle'] = $myapp->getLang('thread_attach_download');
    $myapp->data['errormessage'] = false;
    include $myapp->template('thread/download');
elseif($myapp->data['method']=='POST'):
    #POST 提交进行下载
    if(!empty($_POST['hash'])&&!empty($_POST['time'])):
        $time = intval($_POST['time']);
        $hash = $_POST['hash'];
        if($myapp->data['time'] - $time > 20|| $myapp->data['time'] - $time < 3):
            #超时 太久不下载 3-20秒
            #限制2秒可以加大数据采集拷贝难度,不要超过5秒(影响用户体验)
            #前端原则上 增加下载倒计时
            #必须根据 name=time上计算,有直接X秒倒计时,如X秒前禁用下载按钮,X秒后,提醒用户下载剩余时间
            $myapp->data['errortitle'] = $myapp->getLang('thread_attach_longtime');
            $myapp->data['errormessage'] = $myapp->getLang('thread_attach_longtime_msg');
            include $myapp->template('thread/error');
            $myapp->exit();
        endif;
        #加密向量 $myapp->data['time']
        $iv = $myapp->ivcrypt($time);
        #解密验证
        $decode = $myapp->decrypt(hex2bin($hash),$iv);
        $attach['credits'] = 1;
        if($decode==$attach['aid']):
            #验证通过
            #拦截扣费
            if(!$myapp->plugin_read('thread_attach_play',$attach,$thread)):
                #支付积分
                #附件付费
                $cost = array();#花费
                $win = array();#窃取
                if(!empty($attach['credits']) || !empty($attach['golds']) || !empty($attach['rmbs'])):
                    foreach(['credits','golds','rmbs'] as $v):
                        if($attach[$v]>0):
                            $cost[$v] = $attach[$v];
                            if(settings_value('attach_convert_'.$v,0)!=0):
                                #附件主任获得多少积分转让 attach_convert_手续费
                                $win[$v] = ceil($attach[$v] * settings_value('attach_convert_'.$v,0.5));
                            endif;
                        endif;
                    endforeach;
                endif;
                #默认付费
                if(!empty($myapp->data['settings']['thread_attach_cost'])):
                    $myapp->data['errortitle'] = $myapp->getLang('thread_attach_cost');
                    foreach($myapp->data['settings']['thread_attach_cost'] as $key=>$v):
                        if($v>0):
                            $cost[$key] = $v;
                            #$xv = ceil($attach[$key] * settings_value('attach_convert_'.$v,0.5));
                            if(settings_value('attach_win_'.$v,0)>0):
                                $win[$key] = $attach[$key] * settings_value('attach_convert_'.$v,0.5);
                            endif;
                        endif;
                    endforeach;
                    include $myapp->template('thread/download');
                    $myapp->exit();
                endif;
                if(!empty($cost)):
                    if(!$myapp->data['user']['uid']):
                        #游客 切换登录节目
                        include $myapp->template('member/login');
                        $myapp->exit();
                    endif;
                    foreach($cost as $key=>$value):
                        if(empty($myapp->data['user'][$key]) || $value>$myapp->data['user'][$key]):
                            $myapp->data['allowdown'] = false;
                            $myapp->data['errortitle'] = $myapp->getLang('member_not_money_no_happy');
                            include $myapp->template('thread/download');
                            $myapp->exit();
                        endif;
                    endforeach;
                    $myapp->t('user')->cost($cost,$myapp->data['user']['uid']);
                endif;
                if(!empty($win)):
                    #附件主人获取收益
                    $myapp->t('user')->win($win,$attach['uid']);
                endif;
            endif;
            down_attach($attach,$attachfile);
        endif;
    endif;
    #验证不通过
    $myapp->data['errortitle'] = $myapp->getLang('thread_attach_longtime');
    $myapp->data['errormessage'] = $myapp->getLang('thread_attach_longtime_msg');
    include $myapp->template('thread/error');
    $myapp->exit();

endif;
$myapp->exit();