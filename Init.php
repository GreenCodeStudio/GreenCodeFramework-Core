<?php

error_reporting(E_ALL);
spl_autoload_register(function ($class_name) {
    include __DIR__.'/../'.$class_name.'.php';
});
global $debugType;
$debugType = 'html';
global $debugArray;
$debugArray = [];
include __DIR__.'/../../vendor/autoload.php';
$dotenv = new \Dotenv\Dotenv(__DIR__.'/../../');
$dotenv->load();


include __DIR__.'/Router.php';
include __DIR__.'/Debug.php';
\Core\Router::route($_SERVER['REQUEST_URI']);
