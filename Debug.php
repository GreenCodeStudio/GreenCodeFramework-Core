<?php

function dump()
{
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $args = func_get_args();
    global $debugType;
    global $debugArray;
    if ($debugType == 'html') {

        echo '<div style="background:#ffb; color:#113;border:solid 2px #113;">';
        $pathExploded = explode('/', str_replace('\\', '/', $backtrace[0]['file']));
        echo '<span title="'.htmlspecialchars($backtrace[0]['file']).'">';
        echo "\r\n";
        echo htmlspecialchars(end($pathExploded)).' ('.$backtrace[0]['line'].')';
        echo "\r\n";
        echo '</span>';
        echo '</div><pre style="background:#113; color:#ffb;margin-top:0;">';
        echo "\r\n";
        foreach ($args as $arg) {
            echo htmlspecialchars(print_r($arg, true));
            echo "\r\n";
        }
        echo '</pre>';
    } else if ($debugType == 'text') {

        $pathExploded = explode('/', str_replace('\\', '/', $backtrace[0]['file']));
        echo '----'.end($pathExploded).'----';
        foreach ($args as $arg) {
            echo print_r($arg);
        }
        echo "\r\n";
        echo "\r\n";
    } else if ($debugType == 'console') {
        $vars = [];
        foreach ($args as $arg) {
            $vars[] = print_r($arg, true);
        }
        ob_clean();
        $debugArray[] = ['backtrace' => $backtrace, 'vars' => $vars];
    } else {
        $debugArray[] = ['backtrace' => $backtrace, 'vars' => $args];
    }
}

function dumpTime(bool $fromStart=false)
{
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $args = func_get_args();
    global $debugType;
    global $debugArray;
    global $_dbgTime;
    $t = microtime(true);
    if($fromStart){
        $txt = number_format(($t - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000, 6).'ms';
    }else {
        if (empty($_dbgTime))
            $txt = 'Start';
        else
            $txt = number_format(($t - $_dbgTime) * 1000, 6).'ms';
    }

    if ($debugType == 'html') {

        echo '<div style="background:#ffb; color:#113;border:solid 2px #113;">';
        $pathExploded = explode('/', str_replace('\\', '/', $backtrace[0]['file']));
        echo '<span title="'.htmlspecialchars($backtrace[0]['file']).'">';
        echo "\r\n";
        echo htmlspecialchars(end($pathExploded)).' ('.$backtrace[0]['line'].')';
        echo "\r\n";
        echo '</span>';
        echo '</div><pre style="background:#113; color:#ffb;margin-top:0;">';
        echo "\r\n";
        echo $txt;
        echo "\r\n";
        echo '</pre>';
    } else if ($debugType == 'text') {

        $pathExploded = explode('/', str_replace('\\', '/', $backtrace[0]['file']));
        echo '----'.end($pathExploded).'----';
        echo $txt;
        echo "\r\n";
        echo "\r\n";
    } else if ($debugType == 'console') {
        $vars = [$txt];

        ob_clean();
        $debugArray[] = ['backtrace' => $backtrace, 'vars' => $vars];
    } else {
        $vars = [$txt];

        ob_clean();
        $debugArray[] = ['backtrace' => $backtrace, 'vars' => $vars];
    }
    $_dbgTime = microtime(true);
}
