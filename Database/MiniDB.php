<?php


namespace Core\Database;


class MiniDB
{
    private static $redis;

    public static function GetConnection()
    {
        if(static::$redis===null) {
            static::$redis = new \Redis();
            static::$redis->connect('127.0.0.1');
        }
        return static::$redis;
    }
}