<?php
namespace table;
class table_settings extends base{
    function __construct()
    {
        $this->table = 'settings';
        $this->indexkey = 'name';
    }
    public function value($name)
    {
		$link = $this->connect();
        return $link->result(array('SELECT `value` FROM '.$this->quote_table().' WHERE `name` = ? ;',array($name)),2,0);
    }
}