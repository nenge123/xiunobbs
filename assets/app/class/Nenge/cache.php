<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 缓存方法
 */
namespace Nenge;
use lib\static_app;
class cache{
    use static_app;
    public array $path;
    public array $site;
    public object $myapp;
    public string $rewrite;
    public function __construct($myapp)
    {
        $this->path = $this->get_path();
        $this->site = $this->get_site();
        $myapp->data['path'] = $this->path;
        $myapp->data['site'] = $this->site;
        $this->myapp = $myapp;
        $this->rewrite = $myapp->get_rewrite_check();
    }
    /**
     * 目录路径 function
     *
     * @return void
     */
    public function get_path()
    {
        $data =  array(
            'root'      =>  WEBROOT,
            'assets'    =>  WEBROOT . 'assets\\',
            'css'       =>  WEBROOT . 'assets\\css\\',
            'js'        =>  WEBROOT . 'assets\\js\\',
            'fonts'     =>  WEBROOT . 'assets\\fonts\\',
            'images'    =>  WEBROOT . 'assets\\images\\',
            'vendor'    =>  WEBROOT . 'assets\\vendor\\',
            'plugin'    =>  WEBROOT . 'plugin\\',
            'upload'    =>  WEBROOT . 'upload\\',
            'attach'    =>  WEBROOT . 'upload\\attach\\',
            'avatar'    =>  WEBROOT . 'upload\\avatar\\',
            'forum'     =>  WEBROOT . 'upload\\forum\\',
            'tmp'       =>  WEBROOT . 'upload\\tmp\\',
            'app'       =>  APPROOT,
            'scss'      =>  APPROOT . 'scss\\',
            'lang'      =>  APPROOT . 'lang\\',
            'router'    =>  APPROOT . 'router\\',
            'cache'     =>  APPROOT . 'cache\\',
            'data'      =>  APPROOT . 'cache\\data\\',
            '_template' =>  APPROOT . 'cache\\template\\',
            'class'     =>  APPROOT . 'class\\',
            'nenge'     =>  APPROOT . 'class\\Nenge\\',
            'table'     =>  APPROOT . 'class\\table\\',
            'function'  =>  APPROOT . 'function\\',
            'template'  =>  APPROOT . 'template\\',
        );
        foreach($data as $key=>$value):
            $data[$key] = str_replace('\\',DIRECTORY_SEPARATOR,$value);
        endforeach;
        return $data;
    }
    /**
     * 网站路径 function
     *
     * @return void
     */
    public function get_site()
    {
        $appsite = WEBSITE.str_replace(array(WEBROOT,DIRECTORY_SEPARATOR),array('','/'),APPROOT);
        $assets = WEBSITE.'assets/';
        return array(
            'root'      =>  WEBSITE,
            'assets'    =>  $assets,
            'css'       =>  $assets .'css/',
            'fonts'     =>  $assets .'fonts/',
            'js'        =>  $assets .'js/',
            'images'    =>  $assets .'images/',
            'fonts'     =>  $assets .'fonts/',
            'vendor'    =>  $assets .'vendor/',
            'plugin'    =>  WEBSITE . 'plugin/',
            'upload'    =>  WEBSITE . 'upload/',
            'attach'    =>  WEBSITE . 'upload/attach/',
            'avatar'    =>  WEBSITE . 'upload/avatar/',
            'forum'     =>  WEBSITE . 'upload/forum/',
            'app'       =>  $appsite,
            'template'  =>  $appsite . 'template/',
            'scss'  =>  $appsite . 'scss/',
            'data'      =>  $appsite . 'cache/data/',
            '_template'  =>  $appsite . 'cache/template/',
        );
    }
    public function get_template_files($dir,$basedir)
    {
        $pre = '';
        if($dir!=$basedir):
            $pre = str_replace($basedir,'',$dir);
            $pre = str_replace('\\','/',$pre);
        endif;
        $files = array(
            'include'=>array(),
            'template'=>array(),
        );
        foreach(scandir($dir) as $file):
            if($file!='.'&&$file!='..'):
                if(is_file($dir.$file)):
                    if(strpos($file,'.php')):
                        $files['include'][] = $pre.basename($file,'.php');
                    endif;
                    if(strpos($file,'.htm')):
                        $files['template'][] = $pre.basename($file,'.htm');
                    endif;
                elseif(is_dir($dir.$file.DIRECTORY_SEPARATOR)):
                    $newfiles = $this->get_template_files($dir.$file.DIRECTORY_SEPARATOR,$basedir);
                    $files['include'] += $newfiles['include'];
                    $files['template'] += $newfiles['template'];
                    unset($newfiles);
                endif;
            endif;
        endforeach;
        return $files;
    }
    public function get_css_files($dir,$basedir)
    {
        $pre = '';
        if($dir!=$basedir):
            $pre = str_replace($basedir,'',$dir);
            $pre = str_replace('\\','/',$pre);
        endif;
        $files = array();
        foreach(scandir($dir) as $file):
            if($file!='.'&&$file!='..'):
                if(is_file($dir.$file)):
                    $files[] = $pre.$file;
                elseif(is_dir($dir.$file.DIRECTORY_SEPARATOR)):
                    $files += $this->get_css_files($dir.$file.DIRECTORY_SEPARATOR,$basedir);
                endif;
            endif;
        endforeach;
        return $files;
    }
    public function get_settings()
    {
        $result = $this->myapp->t('settings')->index2column('value');
        foreach($result as $key=>$value):
            if(is_numeric($value)):
                $result[$key] = isFloat($value)?floatval($value):intval($value);
            elseif(is_string($value)):
                if(strpos($key,'array_')!=false):
                    $result[$key] = explode(',',$value);
                elseif(!empty($start = substr($value,0,2))):
                    if($start=='a:'&&substr($value,-1)=='}'):
                        $result[$key] = unserialize($value);
                    endif;
                endif;
            endif;
        endforeach;
        return $result;
    }
    public function get_forum()
    {
        $result = $this->myapp->t('forum')->query();
        $access = $this->myapp->t('access')->query();
        if(!empty($result)):
            usort($result,fn($a,$b)=>$b['rank'] - $a['rank']);
        endif;
        //$A['rank']==$B['rank']?0:$A['rank']<$B['rank']?-1:1);
        $uids = array();
        $forumlist = array();
        $userlist = array();
        $moduids = array_column($result,'moduids');
        $uids = array_filter(explode(',',implode(',',$moduids)),fn($v)=>!empty($v));
        if(!empty($uids)):
            $userlist = $this->myapp->t('user')->values($uids,false,array('uid','username'));
            foreach($userlist as $user):
                $userlist[$user['uid']] = $user['username'];
            endforeach;
        endif;
        foreach($result as $forum):
            $forum['moduids'] = array_filter(explode(',',$forum['moduids']),fn($v)=>!empty($v));
            $forum['moderators'] = array();
            $forum['access'] = array();
            if(!empty($forum['moduids'])):
                $forum['moderators'] = array_map(fn($v)=>$userlist[$v],$forum['moduids']);
            endif;
            $forumlist[$forum['fid']] = $forum;
        endforeach;
        if(!empty($access)):
            foreach($access as $acc):
                $forumlist[$acc['fid']]['access'][$acc['gid']] = $acc;
            endforeach;
        endif;
        return $forumlist;

    }
    public function get_group()
    {
        return $this->myapp->t('group')->order();
    }
    public function get_plugin()
    {
        $ROOT = $this->path['plugin'];
        if ($rootList = scandir($ROOT)) :
            $plugins = array(
                'router'    => array(),
                'include'  => array(),
                'template'  => array(),
                #'class'     =>array(),
                'method'     =>array(),
                'require'     =>array(),
            );
            $langs = array();
            $rootList = array_filter($rootList,fn($v)=>$v!='.'&&$v!='..'&&is_dir($ROOT.$v));
            foreach ($rootList as $pluginName):
                $pluginPath = $this->myapp->get_plugin_dir($pluginName);
                $pluginFile = $pluginPath . 'app.json';
                if (!is_file($pluginFile))continue;
                $pluginData = file_get_contents($pluginFile);
                if (empty($pluginData))continue;
                $pluginData = json_decode($pluginData, !0);
                if (empty($pluginData)) continue;
                $appData = array(
                    'name'=>!empty($pluginData['name'])?$pluginData['name']:$pluginName,
                    'dir'=>$pluginName,
                );
                foreach(array('name','require','var') as $v):
                    $appData[$v] = !empty($pluginData[$v])? $pluginData[$v]:false;
                endforeach;
                foreach(array('available','class','lang','router','style','template') as $v):
                    $appData[$v] = !empty($pluginData[$v])&&$pluginData[$v] === true;
                endforeach;
                $appData['index'] = intval(isset($pluginData['index'])?$pluginData['index']:0);
                if($appData['style']):
                    $appData['css'] = false;
                    $appData['template'] = false;
                endif;
                $plugins['list'][] =$appData;
            endforeach;
            uasort($plugins['list'],fn($a,$b)=>$b['index'] - $a['index']);
            foreach($plugins['list'] as $keyid =>$appData):
                $pluginName = $appData['dir'];
                #$plugins['key'][] = $pluginName;
                /**
                 * 禁用插件
                 */
                if (!$appData['available']) continue;
                if ($appData['require']):
                    $requiredata = $pluginData['require'];
                    if(is_string($pluginData['require'])):
                        $requiredata = explode(',', $requiredata);
                        $requiredata = array_map(fn($m)=>explode('|',$m.'|'),$requiredata);
                        $requiredata = array_column($requiredata,1,0);
                    endif;
                    foreach ($requiredata as $require_name=>$require_url) :
                        if (!in_array($require_name,$rootList)) :
                            $plugins['require'][$require_name] = $require_url;
                            continue 2;
                        endif;
                    endforeach;
                    unset($pluginData['require']);
                endif;
                $templatePath = $this->myapp->get_plugin_dir($pluginName,'template');
                if ($appData['style']) :
                    /**
                     * 模板风格
                     */
                    
                    $cssPath = $this->myapp->get_plugin_dir($pluginName,'css');
                    $styledata = array(
                        'name' => $appData['name'],
                        'dir'  => $pluginName,
                        'css'  => $this->get_css_files($cssPath,$cssPath),
                    );
                    $styledata += $this->get_template_files($templatePath,$templatePath);
                    if($appData['var']):
                        if(is_string($appData['var'])):
                            $arr = explode(';',$pluginData['var']);
                            $stylevar = array();
                            foreach($arr as $v):
                                list($a,$b) = explode(':',trim($v));
                                $stylevar[$a] = $b;
                            endforeach;
                        endif;
                        $styledata['var'] = $stylevar;
                    endif;
                    $plugins['style'][] = $keyid;
                    if(!empty($this->myapp->data['settings']['style_id'])):
                        if($this->myapp->data['settings']['style_id'] == $keyid):
                            $this->myapp->style = $styledata;
                        endif;
                    endif;
                    $this->write_file('style#'.$pluginName.'.php',$styledata);
                endif;
                if ($appData['template']):
                    /**
                     * 模板替换
                     */
                    $templatePath = $this->myapp->get_plugin_dir($pluginName,'template');
                    $templatelist = $this->get_template_files($templatePath,$templatePath);
                    foreach($templatelist as $tpdir => $tpfiles):
                        foreach($tpfiles as $tpname):
                            if(isset($plugins[$tpdir][$tpname])) continue;
                            $plugins[$tpdir][$tpname] = $keyid;
                        endforeach;
                    endforeach;
                endif;
                if ($appData['router']):
                    /**
                     * 路由 替换
                     */
                    $routerPath = $this->myapp->get_plugin_dir($pluginName,'router');
                    foreach(scandir($routerPath) as $file):
                        if($file!='.'&&$file!='..'&&is_file($routerPath.$file)):
                            if(strpos($file,'.inc.php')!==false):
                                $filename = str_replace('.inc.php','',$file);
                                if(isset($plugins['router'][$filename])) continue;
                                $plugins['router'][$filename] = $keyid;
                            endif;
                        endif;
                    endforeach;
                endif;
                #插件类
                if($appData['class']):
                    $classPath = $this->myapp->get_plugin_dir($pluginName,'class').'common.class.php';
                    if(is_file($classPath)):
                        include $classPath;
                        $pluginClassName = str_replace('-','_',$pluginName);
                        $pluginClassName = str_replace('#','_',$pluginClassName);
                        $className = '\\plugin\\'.$pluginClassName.'\\common';
                        if(class_exists($className,false)):
                            #$plugins['class'][$keyid] = $className;
                            foreach(get_class_methods($className) as $method):
                                $classArr = explode('_',$method);
                                if($classArr[0]!='common'&&empty($classArr[1]))continue;
                                if(in_array($classArr,array('read','set','html'))):
                                    $plugins['method'][$method][] = $keyid;
                                endif;
                            endforeach;
                        endif;
                    endif;
                endif;
                if($appData['lang']):
                    $langpath = $classPath = $this->myapp->get_plugin_dir($pluginName,'lang');
                    foreach(scandir($langpath) as $file):
                        if($file!='.'&&$file!='..'&&is_file($langpath.$file)):
                            $filename = basename($file,'.php');
                            if(in_array($filename,$this->myapp->data['langlist'])):
                                if(empty($langs[$filename])) $langs[$filename] = include($this->path['lang'].$file);
                                $langs[$filename] += include($langpath.$file);
                            endif;
                        endif;
                    endforeach;
                endif;
            endforeach;
            if(!empty($langs)):
                $i18n = $this->myapp->get_lang_name();
                foreach($langs as $lang=>$data):
                    if($lang==$i18n):
                        $this->myapp->setLang($data);
                    endif;
                    if(!defined('DEBUG')):
                        $this->write_file('lang#'.$lang.'.php',$data);
                    endif;
                endforeach;
            endif;
            return $plugins;
        endif;
        #write_data
    }
    public function write_data()
    {
        $settings = array(
            'path'      => $this->path,
            'site'      => $this->site,
            'settings'  =>$this->get_settings(),
            'forumlist' =>$this->get_forum(),
            'grouplist' =>$this->get_group(),
            'routerlist'=>$this->get_router(),
            'langlist'  =>$this->get_lang(),
            'database'  =>$this->get_table(),
            'v'         =>$_SERVER['REQUEST_TIME'],
        );
        $this->myapp->data += $settings;
        $this->myapp->mkdir($this->path['data']);
        $this->myapp->mkdir($this->path['_template']);
        $this->myapp->mkdir($this->path['attach']);
        $this->myapp->mkdir($this->path['avatar']);
        $this->myapp->mkdir($this->path['forum']);
        $this->myapp->mkdir($this->path['tmp']);
        if(!defined('DEBUG')):
            $this->write_file('settings.php',$settings);
        endif;
        $this->write_plugin();
    }
    private function get_basedata($name)
    {
        $result = array(
            'path'      =>$this->path,
            'site'      =>$this->site,
            'routerlist'=>$this->get_router(),
            'langlist'  =>$this->get_lang(),
            'database'  =>$this->get_table(),
            'v'         =>$_SERVER['REQUEST_TIME'],
        );
        $list = array('settings','forumlist','grouplist');
        foreach($list as $v):
            if($name==$v):
                continue;
            endif;
            $result[$v] = $this->myapp->data[$v];
        endforeach;
        return $result;
    }
    public function write_settings()
    {
        if(!$this->is_init()):
            return $this->write_data();
        endif;
        $settings = $this->get_basedata('settings');
        $settings['settings'] = $this->get_settings();
        $this->myapp->data['settings'] = $settings['settings'];
        $this->write_file('settings.php',$settings);
    }
    public function write_forum()
    {
        if(!$this->is_init()):
            return $this->write_data();
        endif;
        $settings = $this->get_basedata('forumlist');
        $settings['forumlist'] = $this->get_forum();
        $this->myapp->data['forumlist'] = $settings['forumlist'];
        $this->write_file('settings.php',$settings);
    }
    public function write_group()
    {
        if(!$this->is_init()):
            return $this->write_data();
        endif;
        $settings = $this->get_basedata('grouplist');
        $settings['grouplist'] = $this->get_group();
        $this->myapp->data['grouplist'] = $settings['grouplist'];
        $this->write_file('settings.php',$settings);
    }
    public function write_plugin()
    {
       $plugin = $this->get_plugin();
       if(!empty($plugin)):
            $this->myapp->plugin = $plugin;
            if(!defined('DEBUG'))$this->write_file('plugin.php',$this->myapp->plugin);
       endif;
    }
    public function write_file($file,$data)
    {
        $replace = false;
        if(is_array($data)&&!empty($data['path'])):
            $replace = true;
        endif;
        if (is_array($data)) :
            $data = "\nreturn " . var_export($data, true);
        endif;
        if($replace):
            #$data = str_replace('\''.WEBSITE.'\'','WEBSITE',$data);
            $data = str_replace('\''.WEBSITE,'WEBSITE.\'',$data);
            $data = str_replace('\''.addslashes(APPROOT),'APPROOT.\'',$data);
            $data = str_replace('\''.addslashes(WEBROOT),'WEBROOT.\'',$data);
        endif;
        $data = "<?php \n/**\n * 能哥网\n * @link https://nenge.net\n * 缓存数据\n */\ndefined('APPROOT') or die('return to <a href=\"/\">Home</a>');\n" . $data . ";";
        return file_put_contents($this->path['data'] . $file, $data);
    }
    public function rewrite_iis()
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = !0;
        $doc->preserveWhiteSpace = !0;
        $doc->recover = !0;
        $rewrite_file = WEBROOT . 'web.config';
        $s = "\n";
        $htaccess  = $s.'<rule name="xiuno_rewrite" stopProcessing="true">';
        $htaccess .= $s.'   <match url="^(?!assets|upload|plugin|index\.php)(.*)$"/>';
        $htaccess .= $s.'   <action type="Rewrite" url="index.php?{R:0}"/>';
        $htaccess .= $s.'   <conditions logicalGrouping="MatchAll" trackAllCaptures="false">';
        $htaccess .= $s.'       <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true"/>';
        $htaccess .= $s.'       <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true"/>';
        $htaccess .= $s.'   </conditions>';
        $htaccess .= $s.'</rule>';
        if (is_file($rewrite_file)) {
            @$doc->load($rewrite_file);
            $node_rules = $doc->getElementsByTagName('rewrite')[0]?->getElementsByTagName('rules')[0];
            $nodes = $node_rules?->childNodes;
            $i = 0;
            while($node = $nodes?->item($i++)):
                if($node->nodeType === 3):
                    $node->textContent = '';
                else:
                    if($node->nodeName == 'rule'):
                        $atrr_name = $node->attributes->getNamedItem('name');
                        if($atrr_name?->value == 'xiuno_rewrite'):
                            $node_rules->removeChild($node);
                            $i--;
                            continue;
                        endif;
                    endif;
                endif;
            endwhile;
            if($node = $nodes?->item($nodes->length-1)):
                if($node->nodeType === 3):
                    $node->parentNode->removeChild($node);
                endif;
            endif;
            $frag = $doc->createDocumentFragment();
            $frag->appendXML($htaccess);
            $node_rules->appendChild($frag);
            $data = $doc->saveXML();
        } else {
            $data  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            $data .= "<configuration>\n\t<system.webServer>\n\t\t<rewrite>\n\t\t\t<rules>\n\t\t\t";
            $data .= $htaccess;
            $data .= "</rules>\n\t\t</rewrite>\n\t</system.webServer>\n</configuration>";
        }
        file_put_contents($rewrite_file, $data);
    }
    public function rewrite_apache()
    {
        $htaccess  = "<ifModule mod_rewrite.c>\n";
        $htaccess .= "  RewriteEngine on\n";
        $htaccess .= "  RewriteBase ".$this->site['root']."\n";
        $htaccess .= "  RewriteCond %{REQUEST_FILENAME} !-d\n";
        $htaccess .= "  RewriteCond %{REQUEST_FILENAME} !-f\n";
        $htaccess .= "  #[QSA]    附加查询字符\n";
        $htaccess .= "  #[NC]     忽略大小写\n";
        $htaccess .= "  #[NE]     不对URI转义\n";
        $htaccess .= "  RewriteRule ^(?!assets|upload|plugin|index\.php)(.*)\$ index.php?\$1 [QSA,NC,NE,L]\n";
        $htaccess .= "</ifModule>";
        file_put_contents(WEBROOT . '.htaccess',$htaccess);
    }
    public function set_rewrite()
    {
        if($this->rewrite=='iis'):
            $this->rewrite_iis();
        elseif($this->rewrite=='apache'):
            $this->rewrite_apache();
        endif;
    }
    public function is_init()
    {
        return is_file($this->path['data'].'settings.php');
    }
    public function get_router()
    {
        $roter = array();
        foreach(scandir($this->path['router']) as $file):
            if($file!='.'&&$file!=='..'&&is_file($this->path['router'].$file)):
                $roter[] = basename($file,'.inc.php');
            endif;
        endforeach;
        return $roter;
    }
    public function get_lang()
    {
        $lang = array();
        foreach(scandir($this->path['lang']) as $file):
            if($file!='.'&&$file!=='..'):
                $lang[] = basename($file,'.php');
            endif;
        endforeach;
        return $lang;
    }
    public function get_table()
    {
        $result = dbc::app()->getLink()->show_table();
        $data = array();
        $pre = dbc::app()->getPre();
        foreach($result as $table):
            if(strpos($table,$pre)!==false):
                $data[] = str_replace($pre,'',$table);
            endif;
        endforeach;
        return $data;
    }
}