<?php

namespace Core;

class Migration {
    function readNewStructure(){
        $tables=[];
         $modules = scandir(__DIR__.'/../');
        foreach ($modules as $module) {
            if ($module == '.' || $module == '..') {
                continue;
            }
            $filename = __DIR__.'/../'.$module.'/db.xml';
            if (is_file($filename)) {
                $xml=simplexml_load_string(file_get_contents($filename));
                foreach ($xml->table as $table){
                    dump($table);
                    dump($table->name->__toString());
                    $tables[$table->name->__toString()]=$table;
                }
            }
        }
        return $tables;
    }
    function readOldStructure(){
        $tablesList=DB::get("SHOW TABLES");
        $tables=[];
        foreach($tablesList as $table){
            $tables[$table[0]]=DB::get("SHOW COLUMNS FROM ".$table[0]);
        }
        return $tables;
    }
    function upgrade(){
        $old=$this->readOldStructure();
        $new=$this->readNewStructure();
        foreach($new as $name=>$table){
            if(isset($old[$name])){
                
            }else{
              
            }
        }
    }
}
