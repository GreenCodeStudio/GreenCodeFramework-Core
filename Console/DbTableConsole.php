<?php

namespace Core\Console;

use Core\Database\DBAdminOperations;

class DbTableConsole extends \Core\AbstractController
{
    public function show()
    {
        return (new DBAdminOperations())->listAllTables();
    }

    public function select(string $table)
    {
        return (new DBAdminOperations())->selectFromTable($table);
    }
}