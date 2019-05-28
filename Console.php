<?php
error_reporting(E_ALL);
spl_autoload_register(function ($class_name) {
    include __DIR__.'/../'.str_replace("\\", "/", $class_name).'.php';
});
global $debugType;
$debugType = 'console';
global $debugArray;
$debugArray = [];
include __DIR__.'/../../vendor/autoload.php';
$dotenv = new \Dotenv\Dotenv(__DIR__.'/../../');
$dotenv->load();


include __DIR__.'/Router.php';
include __DIR__.'/Debug.php';
$input = json_decode(file_get_contents('php://stdin'));
\Core\Router::routeConsole($input->controller, $input->action, $input->args);