<?php

namespace Nenge\table;
use Nenge\DB;
class table_thread extends base
{
    public $topthread = array();
    function __construct()
    {
        $this->table = 'thread';
        $this->indexkey = 'tid';
    }
    public function list_by_top($reload = false)
    {
        $myapp = \Nenge\APP::app();
        if (!empty($this->topthread)) return $this->topthread;
        $path = $myapp->data['path']['data'] . 'topthreads.php';
        if (!$reload && is_file($path)) {
            $this->topthread = include($path);
        } else {
            $this->topthread = $this->all(array('top' => array(1, 2, 3)), array('order' => array('top' => 'DESC', 'create_date' => 'DESC'))) ?: array();
            $myapp->write_data($path, $this->topthread);
        }
        return $this->topthread;
    }
    public function indexlist($page = 1, $order = array('create_date'=>'DESC'))
    {
        return $this->list_by_fids(\Nenge\APP::app()->allowforum(), $page, $order);
    }
    public function list_by_fids($forumlist, $page = 1, $order = "", $hastop = true, $size = 15)
    {
        $myapp = \Nenge\APP::app();
        if (empty($page)) $page = 1;
        $where = array();
        $threads = array(
            'top'=>array(),
            'list'=>array()
        );
        if(!empty($forumlist))$where['fid'] = $forumlist;
        $uids = [];
        #隐藏 非置顶1 2 级
        //$where = array('!:top' => array(1, 2, 3));
        if (is_array($forumlist)) {
            #如果允许访问版块不对等则根据可访问版块
            if (count($myapp->data['forumlist']) == count($forumlist)) {
                unset($where['fid']);
            } else if (!array_is_list($forumlist)) {
                $fids = array_unique(array_column($forumlist, 'fid'));
                if (!empty($fids)) {
                    $where['fid'] = $fids;
                }
            }
        }
        if ($page == 1) {
            $topthread = $this->list_by_top();
            $top2 = [];
            $top1 = [];
            foreach ($topthread as $k => $v) {
                if (!empty($where['fid'])&&is_int($where['fid'])) {
                    if ($v['fid'] == $where['fid']) {
                        if ($v['top'] == 3) {
                            $threads['top'][$v['tid']] = $v;
                        }
                        if ($v['top'] == 2) {
                            $top2[$v['tid']] = $v;
                        }
                        if ($v['top'] == 1) {
                            $top1[$v['tid']] = $v;
                        }
                    }
                } else if ((empty($where['fid'])||in_array($v['fid'], $where['fid']) )&& $v['top'] == 3) {
                    $threads['top'][$v['tid']] = $v;
                }
            }
            $threads['top'] += $top2 + $top1;
            if(!empty($threads['top']))$uids = array_column($threads['top'],'uid');
        }
        if(empty($order)){
            $order = array('create_date' => 'DESC');
        }
        $query = array('limit' => array(($page - 1) * $size, $size), 'order' => $order);
        $threads['list'] =  $this->all($where, $query)?: array();
        if(!empty($threads['list']))$uids += array_column($threads['list'],'uid')+array_column($threads['list'],'lastuid');
        $uids = array_unique($uids);
        if(!empty($uids)){
            $userlist = DB::t('user')->uids($uids);
            foreach($threads as $key=>$value){
                foreach($value as $fid=>$v){
                    $uid = $v['uid'];
                    if(!empty($userlist[$uid])){
                        $threads[$key][$fid]['username'] = $userlist[$uid]['username'];
                        $threads[$key][$fid]['gid'] = $userlist[$uid]['gid'];
                    }
                    if($key!='top'){
                        $lastid = $v['lastuid'];
                        if(!empty($userlist[$lastid])){
                            $threads[$key][$fid]['lastuser'] = $userlist[$lastid]['username'];
                            $threads[$key][$fid]['lastgid'] = $userlist[$lastid]['gid'];
                        }
                    }
                }
            }
        }
        return $threads;
    }
    public function get_count_fid()
    {
        return $this->connect()->result_all('SELECT COUNT(`tid`) as `num`,`fid` FROM '.$this->quote_table().' GROUP BY `fid`');
    }
    public function reset_count_posts($tid=false)
    {
        $result = DB::t('post')->get_count_fid($tid);
        if(!empty($result)){
            $data = [];
            if(is_array($result)){
                foreach($result as $k=>$v){
                    $data[] = array($v['num'],$v['tid']);
                }
            }elseif($tid){
                $data = array($result,$tid);
            }
            if(!empty($data))return $this->connect()->result_all('UPDATE '.$this->quote_table().' SET `posts`=? WHERE `tid` = ?;',$data);
        }
        return array();
    }
}
