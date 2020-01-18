<?php

error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", __dir__."/../../tmp/php-error.log");
spl_autoload_register(function ($class_name) {
    $path= __DIR__.'/../'.str_replace("\\", "/", $class_name).'.php';
    if(file_exists($path))
        include_once $path;
});
include_once __DIR__.'/Debug.php';
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    \Core\Log::ErrorHandle($errno, $errstr, $errfile, $errline);
});
global $debugType;
$debugType = 'html';
global $debugArray;
$debugArray = [];
include_once __DIR__.'/../../vendor/autoload.php';

use Dotenv\Repository\Adapter\EnvConstAdapter;
use Dotenv\Repository\Adapter\ServerConstAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Dotenv;

$repository = RepositoryBuilder::create()
    ->withReaders([
        new EnvConstAdapter(),
    ])
    ->withWriters([
        new EnvConstAdapter(),
        new ServerConstAdapter(),
    ])
    ->immutable()
    ->make();
$dotenv = Dotenv::create($repository, __DIR__.'/../../');
$dotenv->load();

include_once __DIR__.'/Router.php';
\Core\DB::init();
\Core\Router::route($_SERVER['REQUEST_URI']);
