<?php
namespace Nenge\table;
use Nenge\DB;
class table_forum_access extends base{
    function __construct()
    {
        $this->table = 'forum_access';
        $this->indexkey = '';
    }
}