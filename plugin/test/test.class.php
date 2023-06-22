<?php
namespace plugin\test;
class test{
    public function template($template,$path,$file)
    {
        return '<!-- plugin_test->template('.$file.') -->'.$template;
    }
}
?>