<?php
/**
 * Created by PhpStorm.
 * User: matri
 * Date: 17.07.2018
 * Time: 22:19
 */

namespace Core;


class DBModel
{
    public const ArchiveMode_OnlyExisting = 1;
    public const ArchiveMode_OnlyRemoved = 2;
    public const ArchiveMode_All = 3;
    public $archiveMode = self::ArchiveMode_All;
    protected static $defaultTable;

    public function __construct($defaultTable)
    {
        static::$defaultTable = $defaultTable;
    }

    public function getById(int $id)
    {
        $defaultTable = static::$defaultTable;
        return DB::get("SELECT * FROM $defaultTable WHERE id = ?", [$id])[0] ?? null;
    }

    public function update(int $id, $data)
    {
        DB::update(static::$defaultTable, $data, $id);
    }

    public function insert($data)
    {
        return DB::insert(static::$defaultTable, $data);
    }

}