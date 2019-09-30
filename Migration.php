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
                        foreach ($old[$name]->columns as $oldTableCol) {
                            if ($oldTableCol->name == $colNew->name) {
                                $oldCol = $oldTableCol;
                                break;
                            }
                        }
                        if ($oldCol) {
                            $sqls[] = 'CHANGE '.DB::safeKey($colNew->name).' '.$this->createColumnSql($colNew);

                        } else {
                            $sqls[] = 'ADD '.$this->createColumnSql($colNew);
                        }

                    }
                    foreach ($old[$name]->index as $indexOld) {
                        $foundIdentical = false;
                        foreach ($tableNew->index as $i => $indexNew) {

                            if ($this->isIndexIdentical($indexNew, $indexOld)) {
                                $foundIdentical = true;
                                $indexNew->addAttribute('foundIdentical', true);
                                break;
                            }
                        }
                        if (!$foundIdentical) {
                            $safe = DB::safeKey($indexOld->name);

                            if ($indexOld->type == 'PRIMARY')
                                $sqls[] = "DROP PRIMARY KEY";
                            else if ($indexOld->type == 'FOREIGN')
                                $sqls[] = "DROP FOREIGN KEY $safe";
                            else
                                $sqls[] = "DROP INDEX $safe";
                        }
                    }

                    foreach ($tableNew->index as $indexNew) {
                        if (!$indexNew->attributes()->foundIdentical) {
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
        $tablesList = DB::get("SELECT TABLE_NAME as name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?", [$schema]);
        $tables = [];
        foreach ($tablesList as $tableName) {
            $table = new \stdClass();
            $table->columns = DB::get("SELECT COLUMN_NAME as name, COLUMN_TYPE as type, EXTRA, IS_NULLABLE as `null` FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?", [$schema, $tableName->name]);
            $table->index = [];
            $indexes = DB::get("SHOW INDEX FROM ".$tableName->name);
            $namedIndexes = [];
            foreach ($indexes as $index) {
                $namedIndexes[$index->Key_name][$index->Seq_in_index - 1] = $index;
            }
            foreach ($namedIndexes as $indexArray) {
                $index = new \stdClass();
                if ($indexArray[0]->Key_name == 'PRIMARY')
                    $index->type = 'PRIMARY';
                else if ($indexArray[0]->Non_unique == 1) {
                    $index->type = 'INDEX';
                } else {
                    $index->type = 'UNIQUE';
                }
                $index->element = array_map(fn ($x) => $x->Column_name, $indexArray);
                $index->name=$indexArray[0]->Key_name;
                $table->index[] = $index;
            }
            $foreignKeys = DB::get("SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL", [$schema, $tableName->name]);
            $namedKeys=[];
            foreach ($foreignKeys as $key) {
                $namedKeys[$key->CONSTRAINT_NAME][$key->ORDINAL_POSITION-1] = $key;
            }
            foreach ($namedKeys as $keyArray) {
                $index = new \stdClass();
                $index->type = 'FOREIGN';
                $index->element = array_map(fn ($x) => $x->COLUMN_NAME, $keyArray);
                $index->name=$keyArray[0]->CONSTRAINT_NAME;
                $table->index[] = $index;
            }
            $tables[$tableName->name] = $table;
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
        if ($indexNew->type != $indexOld->type)
            return false;
        if (count($indexNew->element) != count($indexOld->element))
            return false;
        $i = 0;
        foreach ($indexNew->element as $newElement) {
            if ($newElement != $indexOld->element[$i]) {
                return false;
            }
            $i++;
        }
        return true;
    }

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
            $columnSql = DB::safeKey($element);
            if (isset($element->attributes()->size))
                $columnSql .= "({$element->attributes()->size})";

            $columns[] = $columnSql;
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
