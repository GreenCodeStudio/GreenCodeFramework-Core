<?php
error_reporting(E_ALL);
include_once __DIR__.'/../../vendor/autoload.php';
spl_autoload_register(function ($class_name) {
    $path = __DIR__.'/../'.str_replace("\\", "/", $class_name).'.php';
    if (file_exists($path))
        include_once $path;
});
global $debugType;
$debugType = 'console';
global $debugArray;
$debugArray = [];
$dotenv = \Dotenv\Dotenv::create(__DIR__.'/../../');
$dotenv->load();


include_once __DIR__.'/Router.php';
include_once __DIR__.'/Debug.php';
\Core\DB::init();
$input = json_decode(file_get_contents('php://stdin'));
\Core\Router::routeAsyncJob($input->controller, $input->action, $input->args);