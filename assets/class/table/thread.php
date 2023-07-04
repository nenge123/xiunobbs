<?php

namespace table;

use Nenge\DB;
use Nenge\language;

class table_thread extends base
{
    public $topthread = array();
    function __construct()
    {
        $this->table = 'thread';
        $this->indexkey = 'tid';
    }
    public function list_top($reload = false)
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
    public function get_fids_count()
    {
        return $this->connect()->result_all('SELECT COUNT(`tid`) as `num`,`fid` FROM ' . $this->quote_table() . ' GROUP BY `fid`');
    }
    public function reset_count_posts($tid = false)
    {
        $result = DB::t('post')->get_tids_count($tid);
        if (!empty($result)) {
            $data = [];
            if (is_array($result)) {
                foreach ($result as $k => $v) {
                    $data[] = array($v['num'], $v['tid']);
                }
            } elseif ($tid) {
                $data = array($result, $tid);
            }
            if (!empty($data)) return $this->connect()->result_all('UPDATE ' . $this->quote_table() . ' SET `posts`=? WHERE `tid` = ?;', $data);
        }
        return array();
    }
    public function filter_fids($fids)
    {
        if (empty($fids)) {
            $myapp = \Nenge\APP::app();
            $fids = $myapp->allowforum();
        } elseif (is_array($fids)) {
            if (!array_is_list($fids)) $fids = array_column($fids, 'fid');
        } elseif (is_numeric($fids)) {
            $fids = array(intval($fids));
        } else {
            return array();
        }
        return $fids;
    }
    public function filter_toplist($fids)
    {
        $topthread = $this->list_top() ?: array();
        $toplist = array();
        $top2 = array();
        $top1 = array();
        foreach ($topthread as $k => $v) {
            if (!empty($fids) && is_int($fids)) {
                if ($v['fid'] == $fids) {
                    if ($v['top'] == 3) {
                        $toplist[$v['tid']] = $v;
                    }
                    if ($v['top'] == 2) {
                        $top2[$v['tid']] = $v;
                    }
                    if ($v['top'] == 1) {
                        $top1[$v['tid']] = $v;
                    }
                }
            } else if ((empty($fids) || in_array($v['fid'], $fids)) && $v['top'] == 3) {
                $toplist[$v['tid']] = $v;
            }
        }
        $toplist += $top2 + $top1;
        return $toplist;
    }
    public function filter_where_fids($fids)
    {
        if (!empty($fids)) {
            $where = '';
            $param = array();
            if (is_array($fids)) {
                if (count($fids) > 1) {
                    $where = ' `fid` IN (' . implode(',', array_fill(0, count($fids), '?')) . ')';
                    $param = $fids;
                } else {
                    $where = ' `fid` = ? ';
                    $param[] = $fids[0];
                }
            } elseif (is_string($fids)) {
                $where = ' `fid` = ? ';
                $param[] = $fids;
            }
            return array($where, $param);
        }
        return array('', array());
    }
    public function filter_with_user($threads, $userlist)
    {
        $result = array();
        if (!empty($threads)) {
            foreach ($threads as $k => $v) {
                $v['gid'] = 0;
                $v['lastgid'] = 0;
                if (!empty($v['uid'])) {
                    $uid = $v['uid'];
                    if (!empty($userlist[$uid])) {
                        $v['username'] = $userlist[$uid]['username'];
                        $v['gid'] = $userlist[$uid]['gid'];
                    }
                }
                if (!empty($v['lastuid'])) {
                    $lastid = $v['lastuid'];
                    if (!empty($userlist[$lastid])) {
                        $v['lastuser'] = $userlist[$lastid]['username'];
                        $v['lastgid'] = $userlist[$lastid]['gid'];
                    }
                }
                if (empty($v['username'])) $v['username'] = language::app()->offsetGet('user_name_unknow');
                if (empty($v['lastuser'])) $v['lastuser'] = language::app()->offsetGet('user_name_unknow');
                $result[$v['tid']] = $v;
            }
        }
        return $result;
    }
    public function page_by_time($time, $field = 'last_date', $order = 'DESC', $fids = array(), $limit = 40, $digest = false)
    {
        #此方法只适合 上一页 下一页 翻页,limit xxx,15 据说其实是查询了xxx+15条数据,删了xxx条结果.加上时间条件过滤,永远都是前面15条
        $threads = array();
        $myapp = \Nenge\APP::app();
        $limit = intval($limit);
        $order = $order == 'DESC' ? 'DESC' : 'ASC';
        $field = $field == 'create_date' ? $field : 'last_date';
        $fids = $this->filter_fids($fids);
        //print_r($fids);
        $uids = array();
        $userlist = array();
        list($where, $param) = $this->filter_where_fids($fids);
        if (!empty($time) && is_numeric($time) && strlen($time) >= 10) {
            $param[] = (int)$time;
            $where .= ' AND `' . $field . '` >= ? ';
        } else {
            $threads['top'] = $this->filter_toplist($fids);
            if (!empty($threads['top'])) {
                $uids = array_column($threads['top'], 'uid');
            }
        }
        if ($digest) {
            $where .= ' AND `digest`>0 ';
        }
        if (!empty($where)) $where = ' WHERE ' . $where;
        #print_r($param);
        #echo 'SELECT * FROM '.$this->quote_table().$where.'  ORDER BY `'.$field.'` '.$order.' LIMIT 0,'.$limit;print_r($param);exit;
        $result = $this->connect()->result_all('SELECT * FROM ' . $this->quote_table() . $where . '  ORDER BY `' . $field . '` ' . $order . ' LIMIT 0,' . $limit, $param);
        #print_r($result);exit;
        if (!empty($result)) {
            $threads['starttime'] = $result[0][$field];
            $threads['endtime'] = current($result)[$field];
            $uids += array_column($result, 'uid') + array_column($result, 'lastuid');
        }
        if (!empty($uids)) $userlist = DB::t('user')->uids($uids);
        $threads['list'] = $this->filter_with_user($result, $userlist);
        if (!empty($threads['top'])) {
            $threads['top'] = $this->filter_with_user($threads['top'], $userlist);
        }
        $threads['time'] = $time;
        $threads['user'] = $userlist;
        return $threads;
    }
    public function list_by_page($page=1,$field = 'last_date', $order = 'DESC', $fids = array(), $limit = 40, $digest = false)
    {
        $threads = array();
        $myapp = \Nenge\APP::app();
        $limit = '';
        $order = $order == 'DESC' ? 'DESC' : 'ASC';
        $field = $field == 'create_date' ? $field : 'last_date';
        $fids = $this->filter_fids($fids);
        $uids = array();
        $userlist = array();
        list($where, $param) = $this->filter_where_fids($fids);
        if ($digest) {
            $where .= ' AND `digest`>0 ';
        }
        if (!empty($page) && is_numeric($page)&&$page>=1) {
            $page = intval($page);
            $limit = ' LIMIT '.($page-1)*$limit.','.($page*$limit);
        } else {
            $threads['top'] = $this->filter_toplist($fids);
            if (!empty($threads['top'])) {
                $uids = array_column($threads['top'], 'uid');
            }
            $limit = 'LIMIT '.$limit;
        }
        if (!empty($where)) $where = ' WHERE ' . $where;
        $result = $this->connect()->result_all('SELECT * FROM ' . $this->quote_table() . $where . '  ORDER BY `' . $field . '` ' . $order. $limit, $param);
        if (!empty($result)) {
            $threads['page'] = $page;
            $uids += array_column($result, 'uid') + array_column($result, 'lastuid');
        }
        if (!empty($uids)) $userlist = DB::t('user')->uids($uids);
        $threads['list'] = $this->filter_with_user($result, $userlist);
        if (!empty($threads['top'])) {
            $threads['top'] = $this->filter_with_user($threads['top'], $userlist);
        }
        $threads['user'] = $userlist;
        return $threads;
    }
    public function list_hot($fid,$limit=15)
    {
        return $this->all(
            array('fid'=>$fid)
            ,array(
            'order'=>array(
                'posts'=>'DESC',
                'last_date'=>'DESC'
            ),
            'limit'=>$limit
        ));
    }
}
