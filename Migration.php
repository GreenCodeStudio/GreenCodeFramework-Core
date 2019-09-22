<?php

namespace Core;

abstract class Migration
{
    public static function factory()
    {
        if (getenv('dbDialect') == 'mysql')
            return new MigrationMysql();
        else
            return new MigrationMssql();
    }

    function upgrade()
    {
        $old = $this->readOldStructure();
        $new = $this->readNewStructure();
        foreach ($new as $name => $tableNew) {
            $name = strtolower($name);
            try {
                DB::beginTransaction();
                if (isset($old[$name])) {
                    $sqls = [];
                    foreach ($tableNew->column as $colNew) {
                        $oldCol = null;
                        foreach ($old[$name]['columns'] as $oldtableCol) {
                            if ($oldtableCol['COLUMN_NAME'] == $colNew->name->__toString()) {
                                $oldCol = $oldtableCol;
                                break;
                            }
                        }
                        if ($oldCol) {
                            $sqls[] = 'CHANGE '.DB::safeKey($colNew->name).' '.$this->createColumnSql($colNew);

                        } else {
                            $sqls[] = 'ADD '.$this->createColumnSql($colNew);
                        }

                    }
                    foreach ($old[$name]['index'] as $indexOld) {
                        $findedIdentical = false;
                        foreach ($tableNew->index as $i => $indexNew) {

                            if ($this->isIndexIdentical($indexNew, $indexOld)) {
                                $findedIdentical = true;
                                $indexNew->addAttribute('findedIdentical', true);
                                break;
                            }
                        }
                        if (!$findedIdentical) {
                            $key = $indexOld[0]['Key_name'];
                            if ($key == 'PRIMARY')
                                $sqls[] = "DROP PRIMARY KEY";
                            else {
                                $safe = DB::safeKey($indexOld[0]['Key_name']);
                                $sqls[] = "DROP INDEX $safe";
                            }
                        }
                    }

                    foreach ($tableNew->index as $indexNew) {
                        if (!$indexNew->attributes()->findedIdentical) {
                            $sqls[] = "ADD ".$this->addIndexSQL($indexNew);
                        }
                    }
                    $sqlsString = implode(',', $sqls);
                    $sql = "ALTER TABLE `$name` $sqlsString";
                    dump($sql);
                    DB::query($sql);
                } else {
                    $this->createTable($name, $tableNew);
                }
                DB::commit();
            } catch (\Throwable $ex) {
                dump($ex->getMessage(), $ex->getTraceAsString());
                DB::rollBack();
            }
        }
    }

    function readOldStructure()
    {
        $schema = getenv('dbSchema');
        $tablesList = DB::getArray("SELECT TABLE_NAME as name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?", [$schema]);
        $tables = [];
        foreach ($tablesList as $table) {
            $tables[$table['name']]['columns'] = DB::getArray("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?", [$schema, $table['name']]);
            $tables[$table['name']]['index'] = [];
            $indexes = DB::getArray("SHOW INDEX FROM ".$table['name']);
            foreach ($indexes as $index) {
                $tables[$table['name']]['index'][$index['Key_name']][$index['Seq_in_index'] - 1] = $index;
            }
            $foreignKeys = DB::getArray("SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL", [$schema, $table['name']]);
            foreach ($foreignKeys as $key) {
                $tables[$table['name']]['index'][$key['CONSTRAINT_NAME']][$key['ORDINAL_POSITION'] - 1] = $key;
            }
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
        $safename = DB::safeKey($column->name);
        $col = $safename.' '.$column->type.' '.(strtolower($column->null) == 'yes' ? 'NULL' : 'NOT NULL');
        if (!empty($column->default))
            $col .= ' DEFAULT '.DB::safe($column->default->__toString());
        if (!empty($column->autoincrement) && strtolower($column->autoincrement) == 'yes')
            $col .= " AUTO_INCREMENT";
        return $col;
    }

    private function isIndexIdentical($indexNew, $indexOld)
    {
        if (($indexNew->type ?? 'INDEX').'' != $this->getOldIndexType($indexOld))
            return false;
        if (count($indexNew->element) != count($indexOld))
            return false;
        $i = 0;
        foreach ($indexNew->element as $newElement) {
            if ($newElement->__toString() != $indexOld[$i]['Column_name']) {
                return false;
            }
            $i++;
        }
        return true;
    }

    function getOldIndexType($indexOld)
    {
        if (!empty($indexOld[0]['REFERENCED_TABLE_NAME']))
            return 'FOREIGN';
        else if ($indexOld[0]['Key_name'] == 'PRIMARY')
            return 'PRIMARY';
        else if ($indexOld[0]['Non_unique'] == 1) {
            return 'INDEX';
        } else {
            return 'UNIQUE';
        }
    }

    /**
     * @param $indexNew
     */
    protected function addIndexSQL($indexNew)
    {
        if ($indexNew->type.'' == 'PRIMARY')
            $indexSql = "PRIMARY KEY";
        else if ($indexNew->type.'' == 'UNIQUE')
            $indexSql = "UNIQUE";
        else if ($indexNew->type.'' == 'FULLTEXT')
            $indexSql = "FULLTEXT ";
        else if ($indexNew->type.'' == 'FOREIGN')
            $indexSql = "FOREIGN KEY";
        else
            $indexSql = "INDEX";
        $columns = [];
        foreach ($indexNew->element as $element) {
            $columnSql= DB::safeKey($element);
            if(isset( $element->attributes()->size))
                $columnSql.="({$element->attributes()->size})";

            $columns[]=  $columnSql;
        }
        $indexSql .= ' ('.implode(',', $columns).')';
        if ($indexNew->type.'' == 'FOREIGN') {
            $refColumns = [];
            foreach ($indexNew->reference->element as $refElement) {
                $refColumns[] = DB::safeKey($refElement);
            }
            $indexSql .= " REFERENCES ".DB::safeKey($indexNew->reference->attributes()->name)." (".implode(',', $refColumns).")";
        }
        return $indexSql;
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
            $cols[] = $this->addIndexSQL($index);
        }
        $colsString = implode(',', $cols);
        $safename = DB::safeKey(strtolower($name));
        $sql = "CREATE TABLE $safename ($colsString)";
        dump($sql);
        DB::query($sql);
    }

    function oldStructureToXml()
    {
        $old = $this->readOldStructure();
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><root/>');
        foreach ($old as $tableName => $table) {
            $xmlTable = $xml->addChild('table');
            $xmlTable->name = $tableName;
            foreach ($table['columns'] as $column) {
                $xmlColumn = $xmlTable->addChild('column');
                $xmlColumn->name = $column['COLUMN_NAME'];
                $xmlColumn->type = $column['COLUMN_TYPE'];
                $xmlColumn->null = $column['IS_NULLABLE'];
                if (strpos($column['EXTRA'], 'auto_increment') !== false)
                    $xmlColumn->autoincrement = 'YES';
            }
            foreach ($table['index'] as $index) {
                $xmlIndex = $xmlTable->addChild('index');
                $xmlIndex->type = $this->getOldIndexType($index);
                if ($xmlIndex->type == 'FOREIGN') {
                    foreach ($index as $element) {
                        $xmlIndex->element[] = $element['COLUMN_NAME'];
                    }
                    $xmlReference = $xmlIndex->addChild('reference');
                    $xmlReference->addAttribute('name', $index[0]['REFERENCED_TABLE_NAME']);
                    foreach ($index as $element) {
                        $xmlReference->element[] = $element['REFERENCED_COLUMN_NAME'];
                    }
                } else {
                    foreach ($index as $element) {
                        $xmlIndex->element[] = $element['Column_name'];
                    }
                }
            }
        }
        return $xml->asXML();
    }
}
