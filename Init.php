<?php

include_once __DIR__.'/MainInit.php';
global $debugType;
$debugType = 'string';
\Core\Database\DB::init();
include_once __DIR__.'/Routing/Router.php';
\Core\Routing\Router::routeHttp($_SERVER['REQUEST_URI']);
