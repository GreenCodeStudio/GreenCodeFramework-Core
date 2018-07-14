<?php

function dump()
{
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $args = func_get_args();
    global $debugType;
    global $debugArray;
    if ($debugType == 'html') {

        showDbg($backtrace);
        foreach ($args as $arg) {
            var_dump($arg);
        }
        echo "\r\n";
        echo '</pre>';
    } else if ($debugType == 'text') {

        $pathExploded = explode('/', str_replace('\\', '/', $backtrace[0]['file']));
        echo '----'.end($pathExploded).'----';
        foreach ($args as $arg) {
            var_dump($arg);
        }
        echo "\r\n";
        echo "\r\n";
    } else {
        $debugArray[] = ['backtrace' => $backtrace, 'vars' => $args];
    }
}

function showDbg($backtrace)
{
    echo '<div style="background:#ffb; color:#113;border:solid 2px #113;">';
    $pathExploded = explode('/', str_replace('\\', '/', $backtrace[0]['file']));
    echo '<span title="'.htmlspecialchars($backtrace[0]['file']).'">';
    echo "\r\n";
    echo htmlspecialchars(end($pathExploded)).' ('.$backtrace[0]['line'].')';
    echo "\r\n";
    echo '</span>';
    echo '</div><pre style="background:#113; color:#ffb;margin-top:0;">';
    echo "\r\n";
}

function dumpTime()
{
    global $_dbgTime;
    $t = microtime(true);
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    showDbg($backtrace);
    if (empty($_dbgTime))
        echo 'Start';
    else
        echo number_format(($t - $_dbgTime) * 1000, 6).'ms';
    echo "\r\n";
    echo '</pre>';
    $_dbgTime = microtime(true);
}
