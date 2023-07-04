<?php
namespace table;
use Nenge\DB;
class table_settings extends base{
    function __construct()
    {
        $this->table = 'post';
        $this->indexkey = 'pid';
    }
}