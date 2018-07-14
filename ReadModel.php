<?php
/**
 * Created by PhpStorm.
 * User: matri
 * Date: 14.07.2018
 * Time: 14:35
 */

namespace Core;


class ReadModel
{
    static $defaultTable = null;

    public function __construct($defaultTable)
    {
        static::$defaultTable = $defaultTable;
    }
    public function getDataTable(){

    }
}