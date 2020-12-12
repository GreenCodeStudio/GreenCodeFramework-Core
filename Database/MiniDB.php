<?php


namespace Core\Database;


use Core\Database\MiniDB\FileMiniDB;

class MiniDB
{
    private static $instance;

    public static function GetConnection()
    {
        if(static::$instance===null) {
            if(strtolower($_ENV['miniDB'])==='redis') {
                static::$instance = new \Redis();
                static::$instance->connect('127.0.0.1');
            }else{
                static::$instance = new FileMiniDB();
            }
        }
        return static::$instance;
    }
}