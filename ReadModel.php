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

    public function getDataTable($options)
    {

    }

    public function getById(int $id)
    {
        $defaultTable = static::$defaultTable;
        return DB::get("SELECT * FROM $defaultTable WHERE id = ?", [$id])[0] ?? null;
    }
}