<?php

namespace Tp\Db\Insert;

use Zend\Db\Sql\Insert;

class Replace extends Insert
{
    protected $specifications = [
        self::SPECIFICATION_INSERT => 'REPLACE INTO %1$s (%2$s) VALUES (%3$s)',
        self::SPECIFICATION_SELECT => 'REPLACE INTO %1$s %2$s %3$s',
    ];
}