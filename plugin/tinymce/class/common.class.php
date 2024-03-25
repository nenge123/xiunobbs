<?php
namespace plugin\tinymce;
class common  extends \lib\plugin{
    public function html_footer()
    {
        if(router_value(0)==='thread'):
            //https://lib.baomitu.com/tinymce/7.0.0/tinymce.min.js
            $js = $this->site.'js/tinymce.min.js';
            if(isset($_SERVER['REMOTE_ADDR'])&&!in_array($_SERVER['REMOTE_ADDR'],array('::1','127.0.0.1'))):
                $js = 'https://lib.baomitu.com/tinymce/7.0.0/tinymce.min.js';
            endif;
            return '<script src="'.$js.'"></script><script src="'.$this->site.'js/config.js"></script>';
        endif;
    }
}