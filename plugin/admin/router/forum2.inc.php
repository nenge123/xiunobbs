<?php
$myapp->data['title']= $language['forum_admin'];
$grouplist = $myapp['grouplist'];
unset($grouplist[7]);
#print_r($dbinfo);exit;
if($myapp['method']=='POST'){
    if(isset($_POST['deletefid'])){
        #ajax 删除版块
        if(is_numeric($_POST['deletefid'])){
            $result = $myapp->DB('Delete', 'forum',array('fid'=>intval($_POST['deletefid'])));
            $myapp->getCache('settings', true);
            $myapp->json($result);
        }
        exit;
    }elseif(isset($_POST['deleteaccess'])){
        #ajax 删除版块权限
        if(is_numeric($_POST['deleteaccess'])){
            $result =  $myapp->DB('Delete', 'forum_access',array('fid'=>intval($_POST['deleteaccess'])));
            $myapp->getCache('settings', true);
            $myapp->json($result);
        }
        exit;
    }elseif(isset($_POST['editfid'])){
        #ajax 从缓存中获取编辑版块数据
        if(!empty($myapp['forumlist'][$_POST['editfid']])){
            $forumdata = $myapp['forumlist'][$_POST['editfid']];
            $forumdata['fuplist'] = $myapp['forumsname'];
            $forumdata['brief'] = htmlspecialchars($forumdata['brief']);
            $forumdata['announcement'] = htmlspecialchars($forumdata['announcement']);
            $myapp->json($forumdata);
        }
        exit;
    }elseif(isset($_POST['access'])){
        #ajax 更新版块权限
        $accesslist = [];
        foreach($grouplist as $k=>$v){
            $kv = empty($_POST['access'][$k])?array():$_POST['access'][$k];
            $accesslist[] = array(
                'fid'=>intval($_POST['fid']),
                'gid'=>$k,
                'allowread'=>empty($kv['allowread'])?0:1,
                'allowthread'=>empty($kv['allowthread'])?0:1,
                'allowpost'=>empty($kv['allowpost'])?0:1,
                'allowattach'=>empty($kv['allowattach'])?0:1,
                'allowdown'=>empty($kv['allowdown'])?0:1,
            );
        }
        if(!empty($accesslist)){
            $result = $myapp->DB('Insert', 'forum_access', $accesslist, true);
            $myapp->getCache('settings', true);
            $myapp->json($result);
        }
        exit;
    }elseif(!empty($_POST['fid'])&&!empty($_POST['name'])){
        #ajax 更新版块信息
        $newforum = array(
            'fid'=>intval($_POST['fid']),
            'fup'=>intval($_POST['fup']),
            'rank'=>intval($_POST['rank']),
            'name'=>$_POST['name'],
            'brief'=>$_POST['brief'],
            'announcement'=>$_POST['announcement'],
            'moduids'=>$_POST['moduids'],
        );
        $result = $myapp->DB('Update', 'forum', $newforum,array('fid'=>$newforum['fid']));
        $myapp->getCache('settings', true);
        if(!empty($result['line'])){
            $myapp->json(array('fid'=>$newforum['fid'],'rank'=>$newforum['rank'],'name'=>$newforum['name']));
        }
        exit;
    }
    #添加 或者 更新 版块
    $forumlist = [];
    if(!empty($_POST['forums'])){
        foreach($_POST['forums'] as $k=>$v){
            if(!empty($v['name']))$forumlist[] = array(
                'name'=>$v['name'],
                'rank'=>empty($v['rank'])?0:$v['rank'],
                'fid'=>$v['fid'],
            );
        }
    }
    if(!empty($_POST['newforums'])){
        $newforum_keys = array_keys($_POST['newforums']);
        foreach($_POST['newforums']['name'] as $k=>$v){
            if(!empty($v)){
                $forumlist[] = array(
                    'name'=>$v,
                    'rank'=>empty($_POST['newforums']['rank'][$k])?0:$_POST['newforums']['rank'][$k],
                    'fid'=>null,
                );
            }
            //if(!empty($v['name']))$forumlist[] = $v;
        }
    }
    if(!empty($forumlist)){
        $myapp->DB('Insert', 'forum', $forumlist, true);
        $myapp->getCache('settings', true);
    }
    #print_r($forumlist);exit;
}
include $myapp->getTemplate('admin/forum');