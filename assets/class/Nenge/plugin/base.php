<?php
namespace Nenge\plugin;
class base
{
    public static $_app;
    public $settings=array();
    public function __construct()
    {
        self::$_app = $this;
    }
    public static function app()
    {
        if(empty(self::$_app)){
            $class = get_called_class();
            new $class();
        }
        return self::$_app;
    }
}
?>