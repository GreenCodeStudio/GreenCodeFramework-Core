<?php

include_once __DIR__.'/MainInit.php';
setDumpDebugType('text', false);
include_once __DIR__.'/Routing/Router.php';
\Core\Routing\Router::routeHttp($_SERVER['REQUEST_URI']);
