<?php

namespace Core;

class Migration
{
    function upgrade()
    {
        $old = $this->readOldStructure();
        $new = $this->readNewStructure();
        foreach ($new as $name => $tableNew) {
            try {
                DB::beginTransaction();
                if (isset($old[$name])) {
                    $sqls = [];
                    foreach ($tableNew->column as $colNew) {
                        $oldCol = null;
                        foreach ($old[$name]['columns'] as $oldtableCol) {
                            if ($oldtableCol['Field'] == $colNew->name->__toString()) {
                                $oldCol = $oldtableCol;
                                break;
                            }
                        }
                        if ($oldCol) {
                            $sqls[] = 'CHANGE `'.$colNew->name.'` '.$this->createColumnSql($colNew);

                        } else {
                            $sqls[] = 'ADD '.$this->createColumnSql($colNew);
                        }

                    }


                    foreach ($tableNew->index as $indexNew) {
                        foreach ($old[$name]['index'] as $indexOld) {
dump($indexOld,$indexNew);
                        }
                    }
                    $sqlsString = implode(',', $sqls);
                    $sql = "ALTER TABLE `$name` $sqlsString";
                    DB::query($sql);
                } else {
                    $this->createTable($name, $tableNew);
                }
                DB::commit();
            } catch (\Throwable $ex) {
                DB::rollBack();
            }
        }
    }

    function readOldStructure()
    {
        $tablesList = DB::get("SHOW TABLES");
        $tables = [];
        foreach ($tablesList as $table) {
            $tables[$table[0]]['columns'] = DB::get("SHOW COLUMNS FROM ".$table[0]);
            $tables[$table[0]]['index'] = DB::get("SHOW INDEX FROM ".$table[0]);
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
        if (!empty($column->autoincrement) && $column->autoincrement->__toString() == 'YES')
            $col .= " AUTO_INCREMENT";
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
        foreach ($content->index as $index) {
            $cols[] = $this->createIndexSql($index);
        }
        $colsString = implode(',', $cols);
        $sql = "CREATE TABLE `$name`($colsString)";
        dump($sql);
        DB::query($sql);
    }

    protected function createIndexSql($index)
    {
        $cols = [];
        $type = 'INDEX';
        if ($index->type == 'PRIMARY')
            $type = 'PRIMARY KEY';
        foreach ($index->element as $item) {
            $cols[] = "`$item`";
        }
        $colsJoined = implode(',', $cols);
        return "$type ($colsJoined)";
    }
}
