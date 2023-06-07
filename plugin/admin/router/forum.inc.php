<?php
defined('XIUNO')||die('return to <a href="">Home</a>');
if($myapp['method'] == 'POST'){
    if(empty($myapp->router->do)){
        if(!empty($_POST['fid'])){
            #删除版块
            set_time_limit(0);
            $fid = intval($_POST['fid']);
            $result = $myapp->DB('Delete','forum',array('fid'=>$fid));
            $myapp->DB('Delete','forum_access',array('fid'=>$fid));
            #$tids = $myapp->DB('FetchAll','thread',array('fid'=>$fid),array('select'=>array('tid')));
            if(!empty($fids)){
                $tids = array_column($tids,'tid');
                #$myapp->DB('Delete','thread',array('tid'=>$tids));
                #$myapp->DB('Delete','post',array('tid'=>$tid));
            }
            if(!empty($result))$myapp->getCache('settings',true);
        }else{            
            $updatlist = array();
            if(!empty($_POST['forum'])){
                foreach($_POST['forum'] as $fid=>$v){
                    $updatlist[] = array(
                        'fid'=>$fid,
                        'name'=>$v['name'],
                        'rank'=>$v['rank'],
                    );
                }
            }
            if(!empty($_POST['newforum'])){
                foreach($_POST['newforum'] as $fid=>$v){
                    $updatlist[] = array(
                        'fid'=>null,
                        'name'=>$v,
                        'rank'=>0,
                    );
                }
            }
            if(!empty($updatlist)){
                $myapp->DB('Insert','forum',$updatlist,true);
                $myapp->getCache('settings',true);
            }
        }
    }
}
if(!empty($myapp->router->do)){
    $forum = $myapp['forumlist'][$myapp->router->do];
    include $myapp->getTemplate('admin:forum_edit');
}else{
    include $myapp->getTemplate('admin:forum');
}