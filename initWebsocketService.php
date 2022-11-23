<?php

error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", __dir__."/../../tmp/php-error.log");
spl_autoload_register(function ($class_name) {
    include_once __DIR__.'/../'.str_replace("\\", "/", $class_name).'.php';
});

include_once __DIR__.'/loadDotEnv.php';
include_once __DIR__.'/Debug.php';
include_once __DIR__.'/../../vendor/autoload.php';
\Core\WebSocket\Server::init();
