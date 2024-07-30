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
        try {
            if ($_ENV['logErrors']??'false' == 'false') return;
            if (!empty($_ENV['rabbitmq_server'])) {
                $connection = static::connect();
                $channel = $connection->channel();
                $channel->queue_declare('log', false, false, false, false);

                $msg = new \stdClass();
                $msg->type = 'Request';
                $msg->server = [];
                $msg->hostname = $_SERVER['HTTP_HOST'] ?? null;
                $msg->serverIP = $_SERVER['SERVER_ADDR'] ?? null;
                $msg->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                $msg->debug = $_ENV['debug'] == 'true';
                $msg->machine = gethostname();
                $msg->projectPath = dirname(__DIR__, 2);
                $msg->urlRouting = $url;
                $msg->stamp = (new \DateTime("now", new \DateTimeZone("UTC")))->format('Y-m-d H:i:s.u');
                $msg->user = (\Authorization\Authorization::getUserData());

                $amqpMsg = new AMQPMessage(json_encode($msg));
                $channel->basic_publish($amqpMsg, '', 'log');
            }
        } catch (\Throwable $ex) {
            dump($ex);
        }
    }

    static function connect()
    {
        if (static::$connection == null) {
            static::$connection = new AMQPStreamConnection($_ENV['rabbitmq_server'] ?? 'localhost', 5672, 'guest', 'guest');
        }
        return static::$connection;
    }

    public static function ErrorHandle($errno, $errstr, $errfile, $errline)
    {
        dump("Error on line $errline in file $errfile\r\n$errstr");
        try {
            if (($_ENV['logErrors']??'false') == 'false') return;

            if (!empty($_ENV['rabbitmq_server'])) {
                $connection = static::connect();
                $channel = $connection->channel();
                $channel->queue_declare('log', false, false, false, false);

                $msg = new \stdClass();
                $msg->type = 'Error';
                $msg->lang = "php";
                $msg->level = self::FriendlyErrorType($errno);
                $msg->message = $errstr;
                $msg->file = $errfile;
                $msg->line = $errline;
                $msg->column = null;
                $msg->stamp = (new \DateTime("now", new \DateTimeZone("UTC")))->format('Y-m-d H:i:s.u');
                $msg->server = $_SERVER;
                $msg->machine = gethostname();
                $msg->debug = ($_ENV['debug'] ??'false') == 'true';
                $msg->projectPath = dirname(__DIR__, 2);
                $msg->user = (\Authorization\Authorization::getUserData());
                $msg->stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

                $amqpMsg = new AMQPMessage(json_encode($msg));
                $channel->basic_publish($amqpMsg, '', 'log');
            }
        } catch (\Throwable $ex) {
            dump($ex);
        }
    }

    static function FriendlyErrorType($type)
    {
        switch ($type) {
            case E_ERROR: // 1 //
                return 'E_ERROR';
            case E_WARNING: // 2 //
                return 'E_WARNING';
            case E_PARSE: // 4 //
                return 'E_PARSE';
            case E_NOTICE: // 8 //
                return 'E_NOTICE';
            case E_CORE_ERROR: // 16 //
                return 'E_CORE_ERROR';
            case E_CORE_WARNING: // 32 //
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR: // 64 //
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING: // 128 //
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR: // 256 //
                return 'E_USER_ERROR';
            case E_USER_WARNING: // 512 //
                return 'E_USER_WARNING';
            case E_USER_NOTICE: // 1024 //
                return 'E_USER_NOTICE';
            case E_STRICT: // 2048 //
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR: // 4096 //
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED: // 8192 //
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED: // 16384 //
                return 'E_USER_DEPRECATED';
        }
        return "";
    }

    public static function Exception(\Throwable $ex)
    {
        if (($_ENV['logErrors']??'false') == 'false') return;

        if (!empty($_ENV['rabbitmq_server'])) {
            $connection = static::connect();
            $channel = $connection->channel();
            $channel->queue_declare('log', false, false, false, false);

            $msg = new \stdClass();
            $msg->type = 'Error';
            $msg->lang = "php";
            $msg->level = 'Exception';
            $msg->message = get_class($ex) . "\r\n" . $ex->getMessage();
            $msg->file = $ex->getFile();
            $msg->line = $ex->getLine();
            $msg->column = null;
            $msg->stamp = (new \DateTime("now", new \DateTimeZone("UTC")))->format('Y-m-d H:i:s.u');
            $msg->server = $_SERVER;
            $msg->user = (\Authorization\Authorization::getUserData());
            $msg->stack = $ex->getTrace();

            $amqpMsg = new AMQPMessage(json_encode($msg));
            $channel->basic_publish($amqpMsg, '', 'log');
        }

    }

    public static function FrontException($event)
    {
        if (($_ENV['logErrors']??'false') == 'false') return;

        if (!empty($_ENV['rabbitmq_server'])) {
            $connection = static::connect();
            $channel = $connection->channel();
            $channel->queue_declare('log', false, false, false, false);

            $msg = new \stdClass();
            $msg->type = 'Error';
            $msg->lang = "js";
            $msg->level = 'Exception';
            $msg->message = $event->message ?? null;
            $msg->file = $event->filename ?? null;
            $msg->line = $event->lineno ?? null;
            $msg->column = $event->colno ?? null;
            $msg->stamp = (new \DateTime("now", new \DateTimeZone("UTC")))->format('Y-m-d H:i:s.u');
            $msg->server = $_SERVER;
            $msg->user = (\Authorization\Authorization::getUserData());
            $msg->stack = $event->stack ?? null;

            $amqpMsg = new AMQPMessage(json_encode($msg));
            $channel->basic_publish($amqpMsg, '', 'log');
        }
    }
}
