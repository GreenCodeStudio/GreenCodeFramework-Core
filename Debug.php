<?php
$debugType = 'text';
$debugImmediate = true;
function setDumpDebugType(string $type, bool $immediate)
{
    global $debugType;
    global $debugImmediate;
    $debugType = $type;
    $debugImmediate = $immediate;
    if ($immediate)
        dump_render();
}

function dump()
{
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $args = func_get_args();
    global $debugType;
    global $debugArray;
    global $debugImmediate;
    if ($debugType == 'json') {
        $jsons = [];
        foreach ($args as $var) {
            $jsons[] = \json_encode($var);
        }
        $debugArray[] = ['backtrace' => $backtrace, 'jsons' => $jsons];
    } else {
        $strings = [];
        foreach ($args as $var) {
            $strings[] = print_r($var, true);
        }
        $debugArray[] = ['backtrace' => $backtrace, 'strings' => $strings];
    }
    if ($debugImmediate)
        dump_render();
}

function dumpTime(bool $fromStart = false)
{
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    global $debugType;
    global $debugArray;
    global $debugImmediate;
    global $_dbgTime;
    $t = microtime(true);
    if ($fromStart) {
        $txt = number_format(($t - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000, 6).'ms';
    } else {
        if (empty($_dbgTime))
            $txt = 'Start';
        else
            $txt = number_format(($t - $_dbgTime) * 1000, 6).'ms';
    }

    $debugArray[] = ['backtrace' => $backtrace, 'strings' => [$txt]];

    $_dbgTime = microtime(true);
    if ($debugImmediate)
        dump_render();
}

function dump_render()
{
    global $debugType;
    if ($debugType == 'html')
        dump_render_html();
    else
        dump_render_text();
}

function dump_render_text()
{
    global $debugArray;
    if (($_ENV['debug'] ?? '') == 'true') {
        foreach ($debugArray as $item) {
            $pathExploded = explode('/', str_replace('\\', '/', $item['backtrace'][0]['file']));
            echo "---- ".end($pathExploded)." (".$item['backtrace'][0]['line'].") ----\r\n";
            if (isset($item['strings'])) {
                foreach ($item['strings'] as $str) {
                    echo $str;
                }
            } else {
                foreach ($item['jsons'] as $str) {
                    echo $str;
                }
            }
            echo "\r\n";
            echo "\r\n";
        }
    }
    $debugArray = [];
}

function dump_render_html()
{
    global $debugArray;
    if (($_ENV['debug'] ?? '') == 'true') {
        foreach ($debugArray as $item) {
            echo '<div style="background:#ffb; color:#113;border:solid 2px #113;">';
            $pathExploded = explode('/', str_replace('\\', '/', $item['backtrace'][0]['file']));
            echo '<span title="'.htmlspecialchars($item['backtrace'][0]['file']).'">';
            echo "\r\n";
            echo htmlspecialchars(end($pathExploded)).' ('.$item['backtrace'][0]['line'].')';
            echo "\r\n";
            echo '</span>';
            echo '</div><pre style="background:#113; color:#ffb;margin-top:0;">';
            echo "\r\n";
            if (isset($item['strings'])) {
                foreach ($item['strings'] as $str) {
                    echo htmlspecialchars(print_r($str, true));
                    echo "\r\n";
                }
            } else {
                foreach ($item['jsons'] as $str) {
                    echo htmlspecialchars($str);
                    echo "\r\n";
                }
            }
            echo '</pre>';
        }
    }
    $debugArray = [];
}