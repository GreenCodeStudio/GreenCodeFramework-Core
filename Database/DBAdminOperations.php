<?php

namespace Core\Database;

class DBAdminOperations
{

    public function listAllTables()
    {
        return DB::get("SHOW FULL TABLES");
    }

    public function selectFromTable(string $table)
    {
        return DB::get("SELECT * FROM $table");
    }
}