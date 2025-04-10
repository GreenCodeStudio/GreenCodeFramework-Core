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
        try {
            if (self::isEnabled()) {
                $payload = ['path' => $path, 'message' => $message, 'users' => $users];
                static::connect()->send(json_encode($payload));
            }
        }catch (\WebSocket\ConnectionException $e) {
            dump($e);
            //ingore as it's not critical
        }
    }

    static function connect(): \WebSocket\Client
    {
        if (static::$connection == null) {
            static::$connection = new Client('ws://127.0.0.1:'.$_ENV['websocketPort'].'/server');
        }
        return static::$connection;
    }
    static function isEnabled():bool{
        return !empty($_ENV['websocketPort']);
    }
}
