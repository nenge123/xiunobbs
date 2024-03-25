<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 数据库驱动
 */
namespace lib;
abstract class plugin{
    use static_app;
    public string $path;
    public string $classpath;
    public string $funcpath;
    public string $site;
    public string $js;
    public string $images;
    public function __construct($name='')
    {
        static::$_app = $this;
        if(empty($name)):
            $name = explode('\\',static::class)[1];
        endif;
        $this->path = WEBROOT.'plugin'.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR;
        $this->site = WEBSITE.'plugin/'.$name.'/'; 
        $this->js = WEBSITE.'plugin/'.$name.'/js/'; 
        $this->images = WEBSITE.'plugin/'.$name.'/images/'; 
    }
    public function get_file($file)
    {
        return \Nenge\APP::app()->get_dir_path($this->path.$file);
    }
}