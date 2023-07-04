<?php
namespace table;
use Nenge\DB;
class table_group extends base{
    function __construct()
    {
        $this->table = 'group';
        $this->indexkey = 'gid';
    }
}