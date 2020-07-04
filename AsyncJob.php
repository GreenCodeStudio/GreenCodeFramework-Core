<?php
ini_set('memory_limit', '4G');
error_reporting(E_ALL);
include_once __DIR__.'/../../vendor/autoload.php';
spl_autoload_register(function ($class_name) {
    $path = __DIR__.'/../'.str_replace("\\", "/", $class_name).'.php';
    if (file_exists($path))
        include_once $path;
});

include_once __DIR__.'/loadDotEnv.php';
include_once __DIR__.'/Routing/Router.php';
include_once __DIR__.'/Debug.php';
\Core\Database\DB::init();
$input = json_decode(file_get_contents('php://stdin'));
\Core\Routing\Router::routeAsyncJob($input->controller, $input->action, $input->args);