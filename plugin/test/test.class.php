<?php
namespace Nenge\plugin;
class plugin_test extends base{
    public function template($template,$path,$file)
    {
        return '<!-- plugin_test->template('.$file.') -->'.$template;
    }
}
?>