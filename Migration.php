<?php

namespace Core;

class Migration
{
    function upgrade()
    {
        $old = $this->readOldStructure();
        $new = $this->readNewStructure();
        foreach ($new as $name => $tableNew) {
            if (isset($old[$name])) {
                dump($tableNew);
                $sqls = [];
                foreach ($tableNew->column as $colNew) {
                    $oldCol = null;
                    foreach ($old[$name] as $oldtableCol) {
                        dump($oldtableCol['Field'],$colNew->name);
                        if ($oldtableCol['Field'] == $colNew->name->__toString()) {
                            $oldCol = $oldtableCol;
                            break;
                        }
                    }
                    dump($colNew, $oldCol);
                    if ($oldCol) {
                        $sqls[] = 'CHANGE `'.$colNew->name.'` '.$this->createColumnSql($colNew);

                    } else {
                        $sqls[] = 'ADD '.$this->createColumnSql($colNew);
                    }

                }
                $sqlsString = implode(',', $sqls);
                $sql = "ALTER TABLE `$name` $sqlsString";
                dump($sql);
                DB::query($sql);
            } else {
                $this->createTable($name, $tableNew);
            }
        }
    }

    function readOldStructure()
    {
        $tablesList = DB::get("SHOW TABLES");
        $tables = [];
        foreach ($tablesList as $table) {
            $tables[$table[0]] = DB::get("SHOW COLUMNS FROM ".$table[0]);
        }
        return $tables;
    }

    function readNewStructure()
    {
        $tables = [];
        $modules = scandir(__DIR__.'/../');
        foreach ($modules as $module) {
            if ($module == '.' || $module == '..') {
                continue;
            }
            $filename = __DIR__.'/../'.$module.'/db.xml';
            if (is_file($filename)) {
                $xml = simplexml_load_string(file_get_contents($filename));
                foreach ($xml->table as $table) {
                    $tables[$table->name->__toString()] = $table;
                }
            }
        }
        return $tables;
    }

    /**
     * @param $column
     * @return string
     */
    protected function createColumnSql($column)
    {
        $col = '`'.$column->name.'` '.$column->type.' '.($column->null == 'YES' ? 'NULL' : 'NOT NULL');
        if (!empty($column->default))
            $col .= ' DEFAULT '.DB::safe($column->default->__toString());
        return $col;
    }

    /**
     * @param $name
     * @param $content
     */
    protected function createTable($name, $content)
    {
        $cols = [];
        foreach ($content->column as $column) {
            $cols[] = $this->createColumnSql($column);
        }
        $colsString = implode(',', $cols);
        $sql = "CREATE TABLE `$name`($colsString)";
        dump($sql);
        DB::query($sql);
    }
}
