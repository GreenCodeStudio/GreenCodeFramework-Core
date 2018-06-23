<?php

function dump() {
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $args = func_get_args();
    echo '<div style="background:#ffb; color:#113;border:solid 2px #113;">';
    $pathExploded = explode('/', str_replace('\\', '/', $backtrace[0]['file']));
    echo '<span title="'.htmlspecialchars($backtrace[0]['file']).'">';
    echo "\r\n";
    echo htmlspecialchars(end($pathExploded)).' ('.$backtrace[0]['line'].')';
    echo "\r\n";
    echo'</span>';
    echo'</div><pre style="background:#113; color:#ffb;margin-top:0;">';
    echo "\r\n";
    foreach ($args as $arg) {
        var_dump($arg);
    }
    echo "\r\n";
    echo '</pre>';
}
