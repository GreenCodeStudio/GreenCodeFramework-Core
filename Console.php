<?php

include_once __DIR__.'/MainInit.php';
setDumpDebugType('pwsh', true);

$input = json_decode(preg_replace("/^\xEF\xBB\xBF/", '', file_get_contents('php://stdin')));
\Core\Routing\Router::routeConsole($input->controller, $input->action, $input->args, $input->verbose??false);
