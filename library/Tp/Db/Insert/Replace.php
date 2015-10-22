<?php

use Zend\Db\Sql\Insert;

class InsertReplace extends Insert
{
    protected $specifications = [
        self::SPECIFICATION_INSERT => 'REPLACE INTO %1$s (%2$s) VALUES (%3$s)',
        self::SPECIFICATION_SELECT => 'REPLACE INTO INTO %1$s %2$s %3$s',
    ];
}