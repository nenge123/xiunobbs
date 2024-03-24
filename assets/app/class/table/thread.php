<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 数据库 数据表配置类
 */
namespace table;
use Nenge\APP;
use lib\table;
class thread extends table{
    public array $topthread;
    function __construct()
    {
        $this->table = 'thread';
        $this->indexkey = 'tid';
        /*
        $this->tableInfo = array(
            "tid"=>"int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '帖子编号'",
            "fid"=>"smallint UNSIGNED NOT NULL DEFAULT '0' COMMENT '板块编号'",
            "pid"=>"int UNSIGNED NOT NULL DEFAULT '0' COMMENT '主贴编号'",
            "uid"=>"int UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户编号'",
            #"top"=>"tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '置顶类型'",
            "subject"=>"char(128) NOT NULL DEFAULT '' COMMENT '帖子标题'",
            "views"=>"int UNSIGNED NOT NULL DEFAULT '0' COMMENT '浏览次数'",
            #"mods"=>"tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户编号'",
            "closed"=>"tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '锁贴'",
            "lastdate"=>"int UNSIGNED NOT NULL DEFAULT '0' COMMENT '最后回复时间'",
            "lastuid"=>"int UNSIGNED NOT NULL DEFAULT '0' COMMENT '最后回复用户编号'",
            "lastpid"=>"int UNSIGNED NOT NULL DEFAULT '0' COMMENT '最后回复帖子编号'",
        );
        $this->tableAlter = array(
            "PRIMARY KEY (`tid`)",
            "KEY `fid` (`fid`)",
            "KEY `pid` (`pid`)",
        );
        */
    }
    public function getpost($tid)
    {
        
    }
    public function query_toplist()
    {
        $myapp = APP::app();
        if (isset($this->topthread)) return $this->topthread;
        $filename = 'top_threads.php';
        $path = $myapp->data['path']['data'] .$filename;
        if(defined('DEBUG')||!is_file($path)):
            $param = array(1, 2, 3);
            $sql = ' WHERE {top} IN (?,?,?) ';
            /*
            if(!empty($myapp->data['settings']['index_top_fid'])):
                $fid_str = $this->str_fill_param($myapp->data['settings']['index_top_fid']);
                $sql .=  'AND {fid} IN ('.$fid_str.') ';
                $param = array_merge($param,$myapp->data['settings']['index_top_fid']);
            endif;
            */
            $sql = $this->parse_query($sql.';');
            $this->topthread = $this->query($sql,$param) ?:array();
            if(defined('DEBUG')) return;
            $myapp->set_data_file($filename, $this->topthread);
        else:
            $this->topthread = include($path);
        endif;
        
    }
    public function get_toplist($fids)
    {
        if(!isset($this->topthread)):
            $this->query_toplist();
        endif;
        $myapp = APP::app();
        $forumlist = $myapp->data['forumlist'];
        $forum = false;
        $subforum = array();
        if(is_numeric($fids)&&isset($forumlist[$fids])):
            $forum = $forumlist[$fids];
            if(isset($forum['subforum'])):
                $subforum = array_values($forum['subforum']);
            endif;
        endif;
        $toplist = array();
        $top2 = array();
        $top1 = array();
        foreach($this->topthread as $tid=>$thread):
            switch($thread['top']):
                case 3:{
                    if(is_array($fids)&&!empty($fids)):
                        if(in_array($thread['fid'],$fids)):
                            $toplist[$tid] = $thread;
                        endif;
                    elseif(isset($myapp->data['settings']['thread_allow_top'])):
                        if(in_array($thread['fid'],$myapp->data['settings']['thread_allow_top'])):
                            $toplist[$tid] = $thread;
                        endif;
                    else:
                        $toplist[$tid] = $thread;
                    endif;
                    break;
                }
                case 2:{
                    if($forum):
                        if($thread['fid'] == $forum['fid']):
                            $top2[$tid] = $thread;
                        elseif(in_array($thread['fid'],$subforum)):
                            $top2[$tid] = $thread;
                        endif;
                    endif;
                }
                case 1 :{
                    if($forum):
                        if($thread['fid'] == $forum['fid']):
                            $top1[$tid] = $thread;
                        endif;
                    endif;
                }
            endswitch;
        endforeach;
        return $toplist+$top2 + $top1;
       
    }
    public function getlist()
    {
        $myapp = APP::app();
        $page = intval(router_value('page',1));
        $order = router_value('order','desc');
        $limit = router_value('limit',40);
        $uids = array();
        $userlist = array();
        $sqlarr = array();
        $param = array();
        $toptids = array();
        $threads = array(
            'top'=>array(),
            'list'=>array(),
            'limit'=>$limit,
            'total'=>0,
            'page'=>0,
            'pagecount'=>0,
            'order'=>$order
        );
        $forumlist = $myapp->data['forumlist'];
        if(!empty($myapp->data['router']['fid'])):
            $fids = $myapp->data['router']['fid'];
        elseif(!empty($myapp->data['settings']['index_show_fid'])):
            $fids = $myapp->data['settings']['index_show_fid'];
        else:
            $fids = $myapp->get_forum_display(true);
            if(count($myapp->data['forumlist'])=== count($fids)):
                $fids = array();
            endif;
        endif;
        if($page==1):
            if(!empty($threads['top'] = $myapp->plugin_read('top_threadlist',$fids)?:$this->get_toplist($fids))):
                $uids = array_column($threads['top'], 'uid');
                $toptids = array_column($threads['top'],'tid');
            endif;
        endif;
        if(!empty($fids)):
            if(is_array($fids)):
                $sqlarr[] = ' {fid} IN ('.$this->str_fill_param($fids).')';
                $param = $fids;
            else:             
                $sqlarr[] = ' {fid} = ? ';
                $param[] = $fids;
            endif;
        endif;
        if(!empty($toptids)):
            $sqlarr[] = ' {tid} NOT IN ('.$this->str_fill_param($toptids).')';
            $param = array_merge($param,$toptids);
        endif;
        $sql = implode(' AND ',$sqlarr);
        if(!empty($sql)):
            $sql = ' WHERE '.$sql;
        endif;
        $startline = ($page-1)*$limit;
        $threads['total'] = $this->count($this->parse_query($sql).';',$param);
        $threads['page'] = $page;
        if($threads['total']>0):
            $threads['pagination'] = pagination($threads['total'],$threads['page'],$threads['limit']);
            switch($order):
                case 'tid':
                    $sql .= ' ORDER BY `tid` DESC ';
                break;
                case 'view':
                    $sql .= ' ORDER BY `views` DESC ';
                break;
                default:
                    $sql .= ' ORDER BY `last_date` DESC ';
                break;
            endswitch;
            $sql = $this->parse_query($sql);
            $sql .= ' LIMIT '.$startline.','.$limit.';';
            $threads['pagecount'] = ceil($threads['total']/$limit);
            #$result = $this->query($sql,$param,$this->selectlist);
            $result = $this->query($sql,$param);
            $uids += array_column($result, 'uid');
            $uids += array_column($result, 'lastuid');
        endif;
        if (!empty($uids)):
            $userlist = $myapp->t('user')->threads($uids);
        endif;
        if (!empty($threads['top'])):
            $threads['top'] = $this->parser_threadlist($threads['top'], $userlist,$forumlist);
        endif;
        if (!empty($result)):
            $threads['list'] = $this->parser_threadlist($result, $userlist,$forumlist);
        endif;
        $threads['page'] = $page;
        $threads['user'] = $userlist;
        return $threads;
    }
    public function parser_threadlist($threads,$userlist,$forumlist)
    {
        $myapp = APP::app();
        $threadlist = array();
        foreach ($threads as $k => $thread) {
            $thread['gid'] = 0;
            $thread['lastgid'] = 0;
            $thread += $this->parser_post_user(isset($userlist[$thread['uid']])?$userlist[$thread['uid']]:array());
            $thread += $this->parser_last_user(isset($userlist[$thread['lastuid']])?$userlist[$thread['lastuid']]:array());
            if(isset($forumlist[$thread['fid']])):
                $thread['forumname'] = $forumlist[$thread['fid']]['name'];
            else:
                $thread['forumname'] = APP::app()->getLang('delete_forumname');
            endif;
            $thread['create_date_fmt'] = $myapp->get_time_human($thread['create_date']);
            $thread['last_date_fmt'] = $myapp->get_time_human($thread['last_date']);
            $thread['user_avatar_url'] = $myapp->get_avatar_src($thread['uid']);
            $threadlist[$thread['tid']] = $thread;
        }
        return $threadlist;
    }
    public function parser_post_user($user)
    {
        if(empty($user)):
            return array(
                'username'=> APP::app()->getLang('unknow_username'),
                'groupname'=>APP::app()->get_user_group_name()
            );
        endif;
        return array(
            'username'=>$user['username'],
            'gid'=>$user['gid'],
            'groupname'=>APP::app()->get_user_group_name($user)
        );
    }
    public function parser_last_user($user)
    {
        if(empty($user)):
            return array(
                'lastusername'=> APP::app()->getLang('unknow_username'),
                'lastgroup'=>APP::app()->get_user_group_name()
            );
        endif;
        return array(
            'lastusername'=>$user['username'],
            'lastgid'=>$user['gid'],
            'lastgroup'=>APP::app()->get_user_group_name($user)
        );
    }
}
