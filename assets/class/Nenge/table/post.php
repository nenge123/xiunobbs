<?php
namespace Nenge\table;
use Nenge\DB;
class table_post extends base{
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
        $where = array('tid'=>$tid);
        if($only){
            $where['uid'] = $only;
        }
        $list = $this->all($where,array(
            'order'=>array(
                'isfirst'=>'DESC',
                'create_date'=>$order=='ASC'?'ASC':'DESC'
            ),
            'limit'=>array(($page-1)*$limit,$page*$limit)
        ));
        if(!empty($list)){
            $uids = array_column($list,'uid');
            $userlist = DB::t('user')->uids($uids);
            $postlist = array();
            $posttop = array();
            foreach($list as $pid=>$post){
                $post = \Nenge\message::post($post);
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
}