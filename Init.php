<?php

error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", __dir__."/../../tmp/php-error.log");
spl_autoload_register(function ($class_name) {
    include __DIR__.'/../'.str_replace("\\", "/", $class_name).'.php';
});
include __DIR__.'/Debug.php';
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    \Core\Log::ErrorHandle($errno, $errstr, $errfile, $errline);
});
global $debugType;
$debugType = 'html';
global $debugArray;
$debugArray = [];
include __DIR__.'/../../vendor/autoload.php';
$dotenv = new \Dotenv\Dotenv(__DIR__.'/../../');
$dotenv->load();


include __DIR__.'/Router.php';


\Core\Router::route($_SERVER['REQUEST_URI']);
