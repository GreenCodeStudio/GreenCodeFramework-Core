<?php

namespace Core;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Log
{
    /**
     * @var AMQPStreamConnection
     */
    private static $connection;

    public static function Request(string $url)
    {
        $connection = static::connect();
        $channel = $connection->channel();
        $channel->queue_declare('log', false, false, false, false);

        $msg = new \stdClass();
        $msg->type = 'Request';
        $msg->server = $_SERVER;
        $msg->urlRouting = $url;
        $msg->stamp = (new \DateTime())->format('Y-m-d H:i:s.u');
        $msg->user = ( \Authorization\Authorization::getUserData());

        $msg = new AMQPMessage(json_encode($msg));
        $channel->basic_publish($msg, '', 'log');
        //$channel->close();
        // $connection->close();
    }

    static function connect()
    {
        if (static::$connection == null) {
            static::$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        }
        return static::$connection;
    }
}