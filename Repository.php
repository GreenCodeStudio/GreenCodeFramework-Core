<?php
/**
 * Created by PhpStorm.
 * User: matri
 * Date: 17.07.2018
 * Time: 22:19
 */

namespace Core;


use Core\Database\DB;

abstract class Repository
{
    public const ArchiveMode_OnlyExisting = 1;
    public const ArchiveMode_OnlyRemoved = 2;
    public const ArchiveMode_All = 3;

    public $archiveMode = self::ArchiveMode_All;

    abstract public function defaultTable(): string;

    public function getById(int $id)
    {
        $defaultTable = $this->defaultTable();
        return DB::get("SELECT * FROM $defaultTable WHERE id = ?", [$id])[0] ?? null;
    }

    public function update(int $id, $data)
    {
        DB::update($this->defaultTable(), $data, $id);
    }

    public function insert($data)
    {
        return DB::insert($this->defaultTable(), $data);
    }

    public function insertMultiple($data)
    {
        return DB::insertMultiple($this->defaultTable(), $data);
    }

    public function getSelect()
    {
        $defaultTable = $this->defaultTable();
        return DB::get("SELECT id, id as title FROM $defaultTable");
    }
}