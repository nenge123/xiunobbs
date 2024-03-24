<?php
namespace lib;
/**
 * 自实例化类 trait
 */
trait static_app{
    /**
     * 静态标记
     */
    public static object $_app;
    static public function app()
    {
        if(isset(self::$_app))return static::$_app; 
        $class = static::class;
        return static::$_app = new $class(...func_get_args());
    }
    static public function call_method($method,...$params){
        $app = static::app();
        if(is_callable(array($app,$method))):
            return call_user_func_array(array($app,$method),$params);
        endif;
    }
}