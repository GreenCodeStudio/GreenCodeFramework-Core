<?php

function dump() {
    $args = func_get_args();
    foreach ($args as $arg) {
        var_dump($arg);
    }
}
