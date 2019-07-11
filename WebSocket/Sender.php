<?php


namespace Core\WebSocket;

use WebSocket\Client;

class Sender
{
    /**
     * @var \WebSocket\Client
     */
    static $connection = null;

    static function sendToUsers(array $path, $message = null, ?array $users = null)
    {
        $payload = ['path' => $path, 'message' => $message, 'users' => $users];
        static::connect()->send(json_encode($payload));
    }

    static function connect(): \WebSocket\Client
    {
        if (static::$connection == null) {
            static::$connection = new Client('ws://127.0.0.1:'.getenv('websocketPort').'/server');
        }
        return static::$connection;
    }
}