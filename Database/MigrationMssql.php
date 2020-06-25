<?php
/**
 * Created by PhpStorm.
 * User: matri
 * Date: 17.03.2019
 * Time: 21:04
 */

namespace Core\Database;


class MigrationMssql extends Migration
{
    function readOldStructure()
    {
//TODO uzupełnić
        return [];
    }

    /**
     * @param $column
     * @return string
     */
    protected function createColumnSql($column)
    {
        $safename = DB::safeKey($column->name);
        $col = $safename.' '.$column->type.' '.($column->null == 'YES' ? 'NULL' : 'NOT NULL');
        if (!empty($column->default))
            $col .= ' DEFAULT '.DB::safe($column->default->__toString());
        if (!empty($column->autoincrement) && $column->autoincrement->__toString() == 'YES')
            $col .= " IDENTITY(1,1)";
        return $col;
    }
}