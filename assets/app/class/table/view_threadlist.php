<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 视图类 数据库 数据表配置类
 */
namespace table;
use Nenge\APP;
use lib\table;
class view_threadlist extends table{
    public array $selectlist;
    function __construct()
    {
        $this->table = 'view_threadlist';
        $this->indexkey = 'tid';
        $myapp = APP::app();
        if(!in_array($this->table,$myapp->data['database'])):
            $query = $myapp->t('thread')->str_create_view(array('threadlist'),array('post',false,'{0}.`firstpid` = {1}.`pid`'));
            print_r($query);
            exit;
            $this->exec($query);
        endif;
        $this->selectlist = array(
            "tid",
            "fid",
            "pid",
            "uid",
            "subject",
            "views",
            "closed",
            "lastdate",
            "lastuid",
            "lastpid",
            "createdate",
            "userip",
            "images",
            "files",
            "doctype",
        );
    }
    public function getlist()
    {
        $myapp = APP::app();
        $page = intval(router_value('page',1));
        $order = router_value('order','desc') == 'desc'?'DESC':'ASC';
        $limit = router_value('limit',40);
        $uids = array();
        $userlist = array();
        $field = $order == 'ASC'?'create_date' : 'last_date';
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
            if(!empty($threads['top'] = $myapp->plugin_read('top_threadlist')?:array())):
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
            $sql .= ' ORDER BY {'.$field.'} '.$order;
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
    public function exec_reset()
    {
        
    }
}