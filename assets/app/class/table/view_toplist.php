<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 视图类 数据库 数据表配置类
 */
namespace table;
use Nenge\APP;
class view_toplist extends \lib\table{
    public array $selectlist;
    function __construct()
    {
        $this->table = 'view_toplist';
        $this->indexkey = 'tid';
        $myapp = APP::app();
        if(!in_array($this->table,$myapp->data['database'])):
            $query = $myapp->t('threadtop')->str_create_view(array('thread'),array('post',false,'{0}.`tid` = {1}.`tid`'));
            $this->exec($query);
        endif;
    }
    public function list($fid=false)
    {
        $sql = ' `top` =  ?';
        $param[] = 3;
        if(empty($fid)):
            $forumlist = APP::app()->data['forumlist'];
            $sql = ' OR (`fid` =  ?  AND  `top` > ? )';
            $param[] = $fid;
            $param[] = 0;
            if($forumlist[$fid]['fup']>0):
                $sql .= ' OR ( `fid` = ? AND `top` > ?)';
                $param[] = $forumlist[$fid]['fup'];
                $param[] = 2;
            elseif($forumlist[$fid]['fup']==0):
                foreach($forumlist as $forum):
                    if($forum['fup'] == $fid):
                        $sql .= ' OR ( `fid` = ? AND `top` = ?)';
                        $param[] = $forum['fid'];
                        $param[] = 2;
                    endif;
                endforeach;
            endif;
        endif;
        return $this->index2array('WHERE '.$sql.' ORDER BY `top` DESC ',$param);
    }
}