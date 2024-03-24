<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 目录方法
 */
namespace lib;
trait app_method_get{
    public array $forum_available;
    public array $forum_display;
    /**
     * 递归创建目录
     *
     * @param string $dir
     * @return void
     */
    public function mkdir(string $dir):void
    {
        if (!is_dir($dir)) {
            if(!mkdir($dir,0755, true)):
                throw new \ErrorException($this->getLang('DIR_ERROR'),2002);
            endif;
        }
    }
    /**
     * 递归清除目录
     *
     * @param string $src
     * @param boolean $next
     * @return void
     */
    public function rmdir(string $src,$next=false):void
    {
        if (is_dir($src)):
            foreach (scandir($src) as $link):
                if ($link == '.' or $link == '..') continue;
                $full = $src . $link;
                if (is_dir($full)&&$next):
                    $this->rmdir($full . DIRECTORY_SEPARATOR,$next);
                    rmdir($src);
                elseif(is_file($full)):
                    unlink($full);
                endif;
            endforeach;
        endif;
    }
    /**
     * 格式化地址为标准绝对地址
     *
     * @param string $dir
     * @return string
     */
    public function get_dir_path(string $dir):string
    {
        return str_replace(DIRECTORY_SEPARATOR=='/'?'\\':'/',DIRECTORY_SEPARATOR,$dir);
    }
    /**
     * scss 返回一个文件源绝对地址和对应缓存文件绝对地址
     *
     * @param string $scss
     * @return array
     */
    public function get_scss_path(string $scss):array
    {
        $arr = explode('.',$scss);
        $ext = '.'.array_pop($arr);
        $scss = str_ireplace($ext,'',$scss);
        list($cssname,$plugin) = $this->get_name_of_plugin($scss);
        $outname = strtr($cssname,'/','-').'.css';
        if(isset($this->style['css'])):
            if(empty($plugin)&&in_array($scss,$this->style['css'])):
                $plugin = $this->style['dir'];
            endif;
        endif;
        if(empty($plugin)):
            if($ext=='.scss'):
                $path = $this->data['path']['scss'].$cssname.$ext;
            else:
                $path = $this->data['path']['css'].$cssname.$ext;
            endif;
            $pathlink =  $this->data['path']['css'].$outname;
            $link = $this->data['site']['css'].$outname;
        else:
            $path = $this->get_plugin_dir($plugin,'css').$cssname.$ext;
            $pathlink =  $this->data['path']['css'].$plugin.'-'.$outname;
            $link = $this->data['site']['css'].$plugin.'-'.$outname;
        endif;
        $link .= '?'.$this->data['v'];
        return array($path,$pathlink,$link);
    }
    /**
     * scss文件导入过滤程序
     *
     * @param string $scss
     * @return null|string
     */
    public function get_scss_import(string $scss):null|string
    {
        if(!empty($result = $this->plugin_read('scss_path',$scss))):
            return $result;
        endif;
        if(!empty($result = $this->get_scss_path($scss))):
            return $this->get_dir_path($result[0]);
        endif;
        return null;
    }
    /**
     * 分割字符为两部分
     *
     * @param string $str
     * @return array
     */
    public function get_name_of_plugin(string $str):array
    {
        $data = explode(':',$str);
        $name = array_pop($data);
        $plugin = array_pop($data);
        return array($name,$plugin);
    }
    /**
     * 返回路由文件的绝对地址
     *
     * @param string $str
     * @return string
     */
    public function get_router_link(string $str=''):string
    {
        if(empty($str)):
            if(!empty($_SERVER['HTTP_AJAX_API'])):
                #str_replace 防止非法输入
                $file = 'api/'.basename(str_replace('\\','/',$_SERVER['HTTP_AJAX_API']));
                if(isset($this->plugin['router'][$file])):
                    return $this->get_dir_path($this->get_plugin_dir($this->plugin['router'][$file],'router').$file.'.inc.php');
                endif;
                $apipath = $this->get_dir_path($this->data['path']['router'].$file.'.inc.php');
                if(is_file($apipath)):
                    return $apipath;
                endif;
            endif;
            $plugin = '';
            $name = router_value(0,'index');
            #$name = str_replace('\\','/',$name);
        else:
            list($name,$plugin) = $this->get_name_of_plugin($str);
            #统一路由加载表达式
            #$name = str_replace('\\','/',$name);
        endif;
        if(empty($plugin)):
            if($path = $this->plugin_read($name,$plugin)):
                return $path;
            endif;
            if(isset($this->plugin['router'][$name])):
                $path = $this->get_plugin_dir($this->plugin['router'][$name],'router');
            else:                
                if(!in_array($name,$this->data['routerlist'])){
                    $name = '404';
                }
                $path = $this->data['path']['router'];
                $this->get_plugin_method('common_router',$name);
            endif;
        else:
            $path = $this->get_plugin_dir($plugin,'router');
        endif;
        return $this->get_dir_path($path.$name.'.inc.php');
    }
    /**
     * 非DEBUG模式下
     * 读取缓存数据和插件数据.以及风格数据
     *
     * @return void
     */
    public function get_cache_data():void
    {
        $cachepath = APPROOT.'cache'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR;
        $cachefile = $cachepath.'settings.php';
        if(!defined('DEBUG')&&is_file($cachefile)):
            $this->data += include($cachefile);
            if(is_file($cachepath.'plugin.php')):
                $this->plugin = include($cachepath.'plugin.php');
            endif;
        else:
            $cache = \Nenge\cache::app($this);
            if(!empty($this->conf[1]['pw'])):
                $cache->write_data();
            endif;
            unset($cache);
        endif;
        if(empty($this->style)):
            if(empty($this->style = $this->plugin_read('style')?:array())):
                if(!empty($this->plugin['style'])):
                    if(!empty($this->data['settings']['style_id'])):
                        $style_id = $this->data['settings']['style_id'];
                        if(in_array($style_id,$this->plugin['style'])):
                            $style_dir = $this->get_plugin_dir($style_id);
                            $this->style = include($cachepath.'style#'.$style_dir.'.php');
                        endif;
                    endif;
                endif;
            endif;
        endif;
    }
    /**
     * 保存一个缓存文件
     *
     * @param string $filename
     * @param mixed $data
     * @return void
     */
    public function set_data_file(string $filename,mixed $data):void
    {
        \Nenge\cache::app($this)->write_file($filename,$data);
    }
    /**
     * 加载语言数据
     *
     * @return void
     */
    public function get_lang_data():void
    {
        $i8n = $this->get_lang_name();
        if(empty($this->data['path']))\Nenge\cache::app($this);
        if(defined('DEBUG')):
            #防止意外导致语言载入失败
            $this->language = include($this->data['path']['lang'].$i8n.'.php');
        else:
            $this->language = array();
        endif;
        if(is_file($this->data['path']['data'].'lang#'.$i8n.'.php')):
            $this->language += include($this->data['path']['data'].'lang#'.$i8n.'.php');
        elseif(empty($this->language)):
            $this->language = include($this->data['path']['lang'].$i8n.'.php');
        endif;
    }
    /**
     * 返回可用语言语言名
     *
     * @return string
     */
    public function get_lang_name():string
    {
        $i18n = 'zh-CN';
        $data = $this->data;
        if(!empty($data['settings']['i18n'])&&in_array($data['settings']['i18n'],$data['langlist'])):
            $i18n = $data['settings']['i18n'];
        endif;
        return $i18n;
    }
    public function get_rewrite_check()
    {
        if(isset($_SERVER['IIS_UrlRewriteModule'])):
            return 'iis';
        endif;
        if(function_exists('apache_get_modules')):
            if(in_array('mod_rewrite',apache_get_modules())):
                return 'apache';
            endif;
        endif;
        return '';
    }
    /**
     * 根据插件序列 调用插件方法
     *
     * @param integer $pluginid
     * @param string $method
     * @param array $params
     * @return mixed 返回方法执行结果或者插件对象
     */
    public function get_plugin_class(int $pluginid,string $method='',array $params=array()):mixed
    {
        if(!isset($this->plugin_class[$pluginid])):
            $pluginName = '\\plugin\\'.$this->plugin['list'][$pluginid]['dir'].'\\common';
            $pluginName = str_replace('-','_',$pluginName);
            $pluginName = str_replace('#','_',$pluginName);
            if(!class_exists($pluginName,false)):
                include($this->get_plugin_dir($pluginid,'class').'common.class.php');
            endif;
            $this->plugin_class[$pluginid] = new $pluginName();
        endif;
        if(!empty($this->plugin_class[$pluginid])):
            if(!empty($method)):
                $func = array($this->plugin_class[$pluginid],$method);
                if(is_callable($func)):
                    return call_user_func_array($func,$params);
                endif;
            endif;
            return $this->plugin_class[$pluginid];
        endif;
    }
    /**
     * 对插件方法进行无返回遍历
     *
     * @param string $method
     * @param mixed ...$params
     * @return void
     */
    public function get_plugin_method(string $method,...$params):void
    {
        if(is_array($keys = $this->get_plugin_keys($method))):
            foreach ($keys as $id):
                $this->get_plugin_class($id,$method,...$params);
            endforeach;
        endif;
    }
    /**
     * 返回插件方法列表中的插件索引
     *
     * @param string $method
     * @return array
     */
    public function get_plugin_keys(string $method):array|int
    {
        if(!empty($this->plugin)):
            if(isset($this->plugin['method'][$method])):
                return $this->plugin['method'][$method];
            endif;
        endif;
        return array();
    }
    /**
     *  用字符串连接插件方法返回的字符串
     *
     * @param string $method
     * @param array $params
     * @return string
     */
    public function get_plugin_str(string $method,array $params=array()):string
    {
        $data = '';
        if($keys = $this->get_plugin_keys($method)):
            foreach ($keys as $id):
                $data .= $this->get_plugin_class($id,$method,...$params);
            endforeach;
        endif;
        return $data;
    }
    /**
     * 返回插件目录绝对地址,分目录绝对地址
     * @param string|integer $plugin
     * @param string $dir
     * @return string
     */
    public function get_plugin_dir(string|int $plugin,string $dir=''):string
    {
        if(is_int($plugin)):
            $plugin = $this->get_plugin_id($plugin);
        endif;
        $path = $this->data['path']['plugin'].$plugin.DIRECTORY_SEPARATOR;
        if(!empty($dir))$path .= $dir.DIRECTORY_SEPARATOR;
        $path = $this->get_dir_path($path);
        #$this->mkdir($path);
        return $path;
    }
    /**
     * 返回插件目录名
     * @param integer $id
     * @return string
     */
    public function get_plugin_id(int $id):string
    {
        return $this->plugin['list'][$id]['dir'];
    }
    /**
     * 获取风格中的预定义变量
     *
     * @param string $name
     * @return array|null
     */
    public function get_style(string $name=''):array|null
    {
        if(!empty($this->style)):
            if($name):
                if(isset($this->style['var'][$name])):
                    return $this->style['var'][$name];
                endif;
                return '';
            endif;
            return $this->style;
        endif;
        if($name) return '';
    }
    /**
     * 返回已编译模板地址,
     * 或者准备编译的模板原始地址和缓存地址
     *
     * @param string $router
     * @return array|string
     */
    public function get_template_path(string $router):array|string
    {
        list($name,$plugin) = $this->get_name_of_plugin($router);
        $pre = "";
        $ext = '.htm';
        if(strpos($name,'include/')===0):
            $ext = '.php';
        endif;
        if(empty($plugin)):
            if($path = $this->plugin_read('template',$name)):
                return $path;
            endif;
            if(!empty($this->plugin['include'][$name])):
                $id = $this->plugin['include'][$name];
                return $this->get_plugin_dir($id,'template').$name.'.php';
            endif;
            if(!empty($this->plugin['template'][$name])):
                $ext = '.htm';
                $plugin = $this->get_plugin_id($this->plugin['template'][$name]);
                $path = $this->get_plugin_dir($plugin,'template').$name.'.htm';
                $pre .= $plugin.'_';
            endif;
            if(!empty($this->style)):
                $style = $this->style;

                if(in_array($name,$style['include'])):
                    return $this->get_plugin_dir($style['dir'],'template').$name.'.php';
                endif;
                if(in_array($name,$style['template'])):
                    $ext = '.htm';
                    $path = $this->get_plugin_dir($style['dir'],'template').$name.'.htm';
                    $pre .= $plugin.'_';
                endif;
            endif;
            if(empty($path)):
                $path = $this->data['path']['template'].$name.$ext;
            endif;
        else:
            $path = $this->get_plugin_dir($plugin,'template').$name.$ext;
            $pre .= $plugin.'_';
        endif;
        $path = $this->get_dir_path($path);
        if($ext=='.php') return $path;
        $pre .= str_replace('/','#',$name).'.php';
        return [$path,$pre];
    }
    /**
     * 根据积分动态返回用户组信息 数据库未实现
     *
     * @param array $user
     * @return array
     */
    public function get_user_group(array $user=array()):array
    {
        if(empty($user))$user = $this->data['user'];
        $credits = empty($user['credits'])?0:$user['credits'];
        $gid = $user['gid'];
        if($gid==8&&$credits>0):
            foreach($this->data['grouplist'] as $group):
                if(!empty($group['creditsfrom'])&&$group['creditsfrom']<$credits):
                    $gid = $group['gid'];
                endif;
            endforeach;
        endif;
        return $this->data['grouplist'][$gid];
    }
    /**
     * 返回用户组组名
     *
     * @param array $user
     * @return string
     */
    public function get_user_group_name(array $user = array()):string
    {
        if(empty($user))$user = array('gid'=>0);
        return $this->get_user_group($user)['name'];
    }
    /**
     * 返回当前用户可阅读的分类版块
     *
     * @return array
     */
    public function get_forum_available():array
    {
        if(!isset($this->forum_available)):
            $group = $this->get_user_group($this->data['user']);
            $this->forum_available = array();
            foreach($this->data['forumlist'] as $forum):
                if(isset($forum['access'][$group['gid']])&&$forum['access'][$group['gid']]['allowread']):
                    $this->forum_available[] = $forum['fid'];
                elseif($group['allowread']):
                    $this->forum_available[] = $forum['fid'];
                endif;
            endforeach;
        endif;
        return $this->forum_available;
    }
    /**
     * 返回非隐藏分类 数据库未实现
     *
     * @return array
     */
    public function get_forum_display():array
    {
        #未使用隐藏版块
        #if(!isset($this->forum_display)):
        #    foreach($this->get_forum_available() as $fid):
        #        if(!empty($this->data['forumlist'][$fid]['display'])):
        #            $this->forum_display[] = $fid;
        #        endif;
        #    endforeach;
        #endif;
        #return $this->forum_display;
        return $this->get_forum_available();
    }
    /**
     * 返回可访问导航链接
     *
     * @return void
     */
    public function get_forum_nav():array
    {
        $forumlist = array();
        foreach($this->get_forum_display() as $fid):
            if(empty($this->data['forumlist'][$fid]['fup'])):
                $forumlist[$fid]['name'] = $this->data['forumlist'][$fid]['name'];
            else:
                $forumlist[$this->data['forumlist'][$fid]['fup']]['submenu'][$fid] = $this->data['forumlist'][$fid]['name'];
            endif;
        endforeach;
        return $forumlist;
    }
    /**
     * 返回板块权限
     *
     * @param integer|string $fid
     * @return array
     */
    public function get_forum_access(int|string $fid):array
    {
        $group = $this->get_user_group();
        if(isset($this->data['forumlist'][$fid]['access'][$group['gid']])):
            $group = array_merge($group,$this->data['forumlist'][$fid]['access'][$group['gid']]);
        endif;
        return $group;
    }
    /**
     * 返回权限情况
     * @return boolean
     */
    public function get_forum_access_value(int|string $fid,string $key):bool
    {
        $access = $this->get_forum_access($fid);
        if(isset($access[$key])):
            return !empty($access[$key]);
        endif;
        return false;
    }
    /**
     * 返回主题列表
     * @example location description 参数通过路由传递 router_value
     *
     * @return array
     */
    public function get_forum_threadlist():array
    {
        if(empty($threadlist = $this->plugin_read('forum_thread_list'))?:array()):
            $threadlist = $this->t('thread')->getlist();
        endif;
        return $this->plugin_set('forum_thread_list',$threadlist);
    }
    /**
     * 返回论坛图标
     *
     * @param type $fid
     * @return string
     */
    public function get_forum_icon_src($fid):string
    {
        if(is_file($this->data['path']['forum'].$fid.'.png')):
            return $this->data['site']['forum'].$fid.'.png';
        endif;
        return $this->data['site']['images'].'forum.png';
    }
    public function get_thread_postlist():array
    {
        if(empty($threadlist = $this->plugin_read('thread_post_list')?:array())):
            $threadlist = $this->t('post')->getlist();
        endif;
        return $this->plugin_set('thread_post_list',$threadlist);
    }
    public  function get_time_human(int $timesize):string
    {
        if (strlen($timesize) >= 10) :
            $timesize = $this->data['time'] - $timesize;
        endif;
        if ($timesize <= 60) :
            $str = $timesize . $this->getLang('time_second_ago');
        elseif ($timesize <= 3600) :
            $str = floor($timesize / 60) . $this->getLang('time_minute_ago');
        elseif ($timesize <= 86400) :
            $str = floor($timesize / 3600) . $this->getLang('time_hour_ago');
        elseif ($timesize <= 86400 * 7) :
            $str = floor($timesize / 3600) . $this->getLang('time_day_ago');
        elseif ($timesize <= 86400 * 30) :
            $str = floor($timesize / (86400 * 7)) . $this->getLang('time_week_ago');
        elseif ($timesize <= 86400 * 365) :
            $hours = floor($timesize / (86400 * 30));
            $str = $hours . $this->getLang('time_month_ago');
        else :
            $year = floor($timesize / (86400 * 365));
            $str = $year . $this->getLang('time_year_ago');
        endif;
        return $str;
    }
    /**
     * 生成一个时间向量加密数据,要配合提交中的time解密
     * @example
     *  <input type="hidden" name="time" value="{{ time }}">
     *  <input type="hidden" name="time" value="{{ get_time_hash('明文数据') }}">
     * 时间主要作用限制用户操作频率以及时限,加密数据用于验证真伪
     */
    public function get_time_hash(string|int $key):string
    {
        return bin2hex($this->encrypt($key,$this->ivcrypt($this->data['time'])));
    }
    /**
     * 解密加密数据
     *
     * @param string|integer $key
     * @return string
     */
    public function get_time_decode(string $hash,int $time):string
    {
        return $this->decrypt(hex2bin($hash),$this->ivcrypt($time));
    }
    /**
     * 返回用户头像地址
     */
    public function get_avatar_src(int $uid=0):string
    {
        if($uid>0):
            $path = (substr(sprintf("%09d", $uid), 0, 3) .DIRECTORY_SEPARATOR. $uid) . '.png';
            if (is_file($this->data['path']['avatar'] . $path)) :
                return $this->data['site']['avatar'] . $path;
            endif;
        endif;
        return $this->data['site']['images'] . 'avatar.png';
    }
    /**
     * 返回用户头像
     */
    public function get_avatar($uid=0)
    {
        return '<img src="'.$this->get_avatar_src($uid).'" class="avatar-img"/>';
    }

}