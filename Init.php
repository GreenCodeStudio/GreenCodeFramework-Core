<?php
include __DIR__.'/../../vendor/autoload.php';
$dotenv = new \Dotenv\Dotenv(__DIR__);
$dotenv->load();


include __DIR__.'/router.php';
\Core\Router::route($_SERVER['REQUEST_URI']);