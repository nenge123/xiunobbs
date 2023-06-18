<?php
namespace Nenge\table;
use Nenge\DB;
class table_post extends base{
    public array $list = array();
    function __construct()
    {
        $this->table = 'post';
        $this->indexkey = 'pid';
    }
    public function get_tids_count($tid=array(),$page=0)
    {
        $link = $this->connect();
        if(empty($tid)) {
            $limit = '';
            if($page>0){
                $page -=1;
                $limit = ' LIMIT '.($page*500).',500';
            }
            return $link->result_all('SELECT COUNT(`pid`) as `num`,`tid` FROM '.$this->quote_table().' GROUP BY `tid`'.$limit.';');
        }
        $where = '`tid`=?';
        if(is_array($tid)){
            $where = '`tid` IN('.implode(',',array_fill(0,count($tid),'?')).')';
        }
        if(is_array($tid))return $link->result_all('SELECT COUNT(`pid`) as `num`,`tid` FROM '.$this->quote_table().' WHERE '.$where,$tid);
        return $link->result_first('SELECT COUNT(`pid`) as `num` FROM '.$this->quote_table().' WHERE '.$where,$tid);
    }
    public function postlist($tid,$page=1,$size=15,$order='ASC')
    {
        $limit_start = ($page-1)*15;
        $limit_end = $size;
        $link = $this->connect();
        if($order=='ASC'){            
            if($page==1){
                $limit_end +=1;
            }else{
                $limit_start+=1;
            }
        }else{
            $order = 'DESC';
        }
        $result = $link->result_all('SELECT * FROM '.$this->quote_table().' WHERE `tid`=? ORDER BY `create_date` '.$order,array($tid));
        return $result;

    }
    public function page_by_tid($tid,$page=1,$order='ASC',$only=false,$limit=40)
    {
        #SELECT *  FROM `bbs_post` WHERE `tid` = 12  ORDER BY `bbs_post`.`isfirst` DESC,`bbs_post`.`create_date` ASC LIMIT 1,3;
        $language = \Nenge\language::app();
        $where = array('tid'=>$tid);
        if($only){
            $where['uid'] = $only;
        }
        $list = $this->all($where,array(
            'order'=>array(
                'isfirst'=>'DESC',
                'create_date'=>$order=='DESC'?'DESC':'ASC'
            ),
            'limit'=>array(($page-1)*$limit,$page*$limit)
        ));
        if(!empty($list)){
            $this->list += $list;
            $quotelist = $this->pids(array_column($list,'quotepid'));
            $uids = array_column($list,'uid');
            if(!empty($quotelist)){
                $uids = array_column($quotelist,'uid');
            }
            $userlist = DB::t('user')->uids($uids);
            $postlist = array();
            $posttop = array();
            $attachlist = array();
            foreach($list as $post){
                if(!empty($post['images']) || !empty($post['files'])){
                    $aids[] = $post['pid'];
                }
            }
            if(!empty($aids)){
                $attachlist = DB::t('attach')->fetch_by_pids($aids);
                if(!empty($attachlist)){
                    foreach($attachlist as $v){
                        $list[$v['pid']]['attach'][$v['aid']] = $v;
                    }
                }
            }
            foreach($list as $pid=>$post){
                if(!empty($post['uid'])){
                    if(isset($userlist[$post['uid']])){
                        $post['username'] = $userlist[$post['uid']]['username'];
                        $post['gid'] = $userlist[$post['uid']]['gid'];
                    }else{
                        $post['gid'] = 7;
                        $post['uid'] = -1;
                        $post['username'] = $language['user_name_delete'];
                    }
                }else{
                    $post['username'] = $language['user_name_unknow'];
                }
                $post = \Nenge\message::post($post);
                if(isset($post['quotepid'])&&isset($quotelist[$post['quotepid']])){
                    $post['quoteuid'] = $quotelist[$post['quotepid']]['uid'];
                    $post['blockquote'] = \Nenge\message::post_sub($quotelist[$post['quotepid']],300)['message'];
                }
                if($post['isfirst']){
                    $posttop = $post;
                }else{
                    $postlist[$pid] = $post;
                }
            }
            return array(
                'list'=>$postlist,
                'first'=>$posttop,
                'user'=>$userlist
            );

        }
    }
    public function pids($pids)
    {
        if(is_numeric($pids)){
            if(isset($this->list[$pids])) return $this->list[$pids];
            else {
                $result = $this->fetch_by_pids($pids);
                if(!empty($result[$pids])) return $result[$pids];
            }
            return $result;
        }
        elseif(is_string($pids)) return array();
        $pids = array_unique($pids);
        $newpids = [];
        $result = [];
        foreach($pids as $k=>$v){
            if(empty($v))continue;
            $v = (int) $v;
            if(empty($this->list[$v])){
                $newpids[] = $v;
            }else{
                $result[$v] = $this->list[$v];
            }
        }
        return $result+$this->fetch_by_pids($newpids);
    }
    public function fetch_by_pids($pids)
    {
        if(empty($uids))return array();
        $result = $this->all(array('uid'=>$uids));
        if(!empty($result))$this->list+=$result;
        else $result = array();
        return $result;
    }
}