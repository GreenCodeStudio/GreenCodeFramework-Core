<?php


namespace Core\Database;


class MiniDB
{
    private static $redis;

    public static function GetConnection(): \Redis
    {
        if (static::$redis === null) {
            static::$redis = new \Redis();
            static::$redis->connect($_ENV['redis'] ?? '127.0.0.1');
        }
        return static::$redis;
    }
}