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
global $debugArray;
$debugArray = [];
include_once __DIR__.'/../../vendor/autoload.php';

include_once __DIR__.'/loadDotEnv.php';


function t($q){
    return \Core\Internationalization\Translator::$default->translate($q)->__toString();
}
if(empty($_COOKIE["uniq"]))
    setcookie("uniq",bin2hex(openssl_random_pseudo_bytes(4)).uniqid(), time()+365*24*60*60);
include_once __DIR__.'/Annotations.php';
\Core\Database\DB::init();