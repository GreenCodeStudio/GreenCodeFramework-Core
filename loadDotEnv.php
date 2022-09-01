<?php

include_once __DIR__ . '/../vendor/autoload.php';


if (is_file(__DIR__ . '/../../.env')) {
    $content = file_get_contents(__DIR__ . '/../../.env');
    foreach (explode("\n", $content) as $line) {
        $equalIndex = strpos($line, '=');
        if ($equalIndex > 0) {
            $name = trim(substr($line, 0, $equalIndex));
            $value = trim(substr($line, $equalIndex + 1));
            if ($value[0] == "'") {
                $value = substr($value, 1, -1);
            }
            $_ENV[$name] = $value;
        }
    }
}

