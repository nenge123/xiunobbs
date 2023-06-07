<?php
namespace Nenge\table;
use Nenge\DB;
class table_attach extends base{
    function __construct()
    {
        $this->table = 'attach';
        $this->indexkey = 'aid';
    }
}