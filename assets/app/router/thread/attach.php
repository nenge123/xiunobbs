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
$attach = false;
if (empty($thread)) :
    #没有阅读权限 阻止意外加载,不在主题模块
    $msgtitle = 'thread_unable_read';
    $msgcontent = 'thread_unable_read_msg';
    goto showMsg;
endif;
#附件下载
if (empty($access['allowdown'])) :
    #无权下载附件 权限不足
    $msgtitle = 'thread_unable_down';
    $msgcontent =  'thread_unable_down_msg';
    goto showMsg;
endif;
$aid = router_value(3);
if (empty($aid) || !is_numeric($aid)) :
    #参数异常 不是附件ID
    $msgtitle = 'error_input';
    $msgcontent =  'error_input_msg';
    goto showMsg;
endif;
$attach = $myapp->t('attach')->value($aid);
if (empty($attach)) :
    #数据异常 附件数据不存在
    $msgtitle = 'thread_attach_exists';
    $msgcontent =  'thread_attach_exists_msg';
    goto showMsg;
endif;
$attachfile = $myapp->get_dir_path($myapp->data['path']['attach'] . $attach['filename']);
#$attachfile = $myapp->data['path']['root'] . 'push.sh';
if (!is_file($attachfile)) :
    #IO异常 附件文件不存在
    $msgtitle = 'thread_null_attach';
    $msgcontent =  'thread_null_attach_msg';
    $attach = false;
    goto showMsg;
endif;
#读取限制信息
$attach_limit_time = settings_value('attach_limit_time', 5);
$attach_max_time = settings_value('attach_max_time', 30);
$myapp->data['attach_limit_time'] = $myapp->data['time'] + $attach_limit_time;
$myapp->data['attach_max_time'] = $myapp->data['time'] + $attach_max_time;
#当前地址
$myapp->data['attach_link'] = $threadhref . 'aid-' . $attach['aid'];

#调试 积分测试
#$myapp->data['user']['uid'] = 1;
#$myapp->data['user']['credits'] = 2;
#$myapp->data['settings']['thread_attach_cost']['credits'] = 1;
#$myapp->data['settings']['thread_attach_cost']['golds'] = 1;

#读取收费信息 read_thread_attach_cost
$myapp->data['extcost'] = $myapp->plugin_read('thread_attach_cost', $attach, $thread);
if(empty($myapp->data['extcost'])) :
    #付费拦截 附件的字段应该理解为盈利 故删除
    #if(!empty($attach['credits']) || !empty($attach['golds']) || !empty($attach['rmbs'])):
    #附件付费
    #    $msgtitle = 'thread_attach_cost');
    #    foreach(['credits','golds','rmbs'] as $v):
    #        if($attach[$v]>0):
    #            $myapp->data['extcost'][$v] = $attach[$v];
    #        endif;
    #    endforeach;
    #endif;
    #后台设置 thread_attach_cost 默认付费
    $myapp->data['extcost'] = array();
    if (!empty($myapp->data['settings']['thread_attach_cost'])) :
        foreach ($myapp->data['settings']['thread_attach_cost'] as $key => $v) :
            if ($v > 0) :
                $myapp->data['extcost'][$key] = intval($v);
            endif;
        endforeach;
    endif;
endif;
#过滤收费信息 reset_thread_attach_cost
$myapp->data['extcost'] = $myapp->plugin_set('thread_attach_cost', $myapp->data['extcost']);
if (!empty($myapp->data['extcost'])) :
    #存在收费信息
    $attach_check = false;
    foreach ($myapp->data['extcost'] as $key => $value) :
        if (!isset($myapp->data['user'][$key])) $myapp->data['user'][$key] = 0;
        #没有积分 或者 积分为负数
        if ($value > $myapp->data['user'][$key]) :
            $attach_check = true;
        endif;
    endforeach;
    #积分不达标
    if($attach_check):
        $attach = false;
        if(empty($myapp->data['user']['uid'])):
            $msgtitle = 'member_required_login';
        else:
            $msgtitle = 'member_no_money_no_happy';
        endif;
        goto showMsg;
    endif;
endif;
#所有检查通过 准备下载
if ($myapp->data['method'] == 'GET') :
    #可以下载
    $msgtitle = 'thread_attach_download';
    $myapp->data['msgcontent'] =  $myapp->getLang(
        'thread_attach_longtime_msg',
        array('limit' => $attach_limit_time, 'max' => $attach_max_time)
    );
    goto showMsg;
endif;
#POST下载
if ($myapp->data['method'] == 'POST') :
    #POST 提交进行下载
    $attachHash = input_post('hash', false);
    $attachTime = input_post('time', false);
    if (!$attachHash || !$attachTime) :
        #参数缺失
        $msgcontent =  'thread_attach_exists_msg';
        goto showMsg;
    endif;
    $checkTime = $myapp->data['time'] - $attachTime;
    if ($checkTime < $attach_limit_time &&$checkTime > $attach_max_time) :
        #限制2秒可以加大数据采集拷贝难度,不要超过5秒(影响用户体验)
        #前端原则上 增加下载倒计时
        #必须根据 name=time上计算,有直接X秒倒计时,如X秒前禁用下载按钮,X秒后,提醒用户下载剩余时间
        $msgtitle = 'thread_attach_longtime';
        $myapp->data['msgcontent'] = $myapp->getLang(
            'thread_attach_longtime_msg',
            array('limit' => $attach_limit_time, 'max' => $attach_max_time)
        );
        #goto showMsg;
    endif;
    if ($myapp->get_time_decode($attachHash, $attachTime) != $attach['aid']) :
        #验证不通过
        goto showMsg;
    endif;
    #验证通过 付费环节
    #$myapp->data['extcost']['golds']  = 1;
    #$myapp->data['user']['golds']  = 1;
    if (!empty($myapp->data['extcost']) && $myapp->data['user']['uid'] < 1) :
        #游客
        $msgtitle = 'thread_attach_notdown';
        $msgcontent = 'member_required_login';
        $myapp->data['extcost'] = array();
        $attach = false;
        goto showMsg;
    endif;
    #读取附件主人可获得积分
    $myapp->data['extwin'] = $myapp->plugin_read('thread_attach_win', $attach, $thread);
    if (empty($myapp->data['extwin'])):
        #没从插件获取 读取设置
        $myapp->data['extwin'] = array();
        #积分转换
        $attach_win = settings_value('thread_attach_win');
        $attach_convert = settings_value('thread_attach_convert');
        if (!empty($attach_win)) :
            $myapp->data['extwin'] = $attach_win;
        elseif (is_array($attach_convert)) :
            foreach ($attach_convert as $key => $value) :
                if (isset($myapp->data['extcost'][$key])) :
                    $value = ceil($myapp->data['extcost'][$key] * $value);
                    if($value>0) $myapp->data['extwin'][$key] = ceil($myapp->data['extcost'][$key] * $value);
                endif;
            endforeach;
        endif;
    endif;
    $myapp->data['extwin'] = $myapp->plugin_set('thread_attach_win', $myapp->data['extwin'], $attach, $thread);
    goto downloads;
endif;
$myapp->exit();
showMsg:
#验证不通过
if (isset($msgtitle)) $myapp->data['msgtitle'] = $myapp->getLang($msgtitle);
if (isset($msgcontent)) $myapp->data['msgcontent'] = $myapp->getLang($msgcontent);
$myapp->data['attach'] = $attach;
include $myapp->template('msg/ajax');
$myapp->exit();

downloads:
#error_reporting(1);
$myapp->data['error_no_report'] = true;
$fp = @fopen($attachfile, "rb",);
if(!$fp){
    $msgtitle = 'thread_attach_notdown';
    $msgcontent = 'thread_attach_not_open';
    $attach = false;
    goto showMsg;
}
ob_clean();
$fstat = fstat($fp);
$fileinfo = pathinfo($attach['orgfilename']);
$extension = $fileinfo['extension'];
if (!empty($myapp->data['extcost'])) :
    $myapp->t('user')->cost($myapp->data['extcost'], $myapp->data['user']['uid']);
endif;
if (!empty($myapp->data['extwin'])) :
    $myapp->t('user')->win($myapp->data['extwin'], $attach['uid']);
    $attach_update = array('downloads' => $attach['downloads'] + 1);
    foreach ($myapp->data['extwin'] as $key => $value) :
        if (empty($attach[$key])) $attach[$key] = 0;
        $attach_update[$key] = $attach[$key] + $value;
    endforeach;
    $myapp->t('attach')->update2json($attach_update, 'aid', $attach['aid']);
else :
    $myapp->t('attach')->add_download($attach['aid'], 1);
endif;
#header('Cache-control: max-age=0, must-revalidate, post-check=0, pre-check=0');
#header('Cache-control: max-age=86400');
header('Cache-control: no-cache');
if (!empty($data['isimage']) || in_array($extension, array('gif', 'jpg', 'jpeg', 'bmp', 'webp', 'png', 'avif', 'apng'))) {
    header('Content-type: image/' . $extension);
} else if (in_array($extension, array('zip', '7z', 'rar'))) {
    header('Content-type: application/x-' . $extension . '-compressed');
} else {
    header('Content-type: application/octet-stream');
}
header('Content-Transfer-Encoding: binary');
header('Content-Disposition: attachment;filename="' . urlencode($fileinfo['basename']) . '"');
header('Content-Length: ' . $fstat['size']);
header('Accept-Ranges:bytes');
#header('Date:'.gmdate('D, d M Y H:i:s',$fstat['ctime']).' GMT');
header('Last-Modified:' . gmdate('D, d M Y H:i:s', $fstat['mtime']) . ' GMT');
#header('Last-Modified:'.date('D, d M Y H:i:s \\G\\M\\TO',$fstat['mtime']));
#header('Content-Type: application/octet-stream');
fpassthru($fp);
fclose($fp);
$myapp->exit();
