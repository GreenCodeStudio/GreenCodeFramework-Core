<?php
try {
    include_once __DIR__.'/MainInit.php';
    if (in_array('--pwsh', $argv)) {
        setDumpDebugType('pwsh', true);

        $input = json_decode(preg_replace("/^\xEF\xBB\xBF/", '', file_get_contents('php://stdin')));
        \Core\Routing\Router::routeConsole($input->controller, $input->action, $input->args, $input->verbose ?? false);
    } else {
        \Core\Routing\Router::routeConsole($argv[1], $argv[2], [], true);
    }
}catch (\Throwable $e){
    var_dump($e);
}
