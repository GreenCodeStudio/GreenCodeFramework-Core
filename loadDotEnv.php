<?php

include_once __DIR__.'/../../vendor/autoload.php';

if (is_file(__DIR__.'/../../.env.local')) {
    loadEnvFile(__DIR__.'/../../.env.local');
}

if (is_file(__DIR__.'/../../.env.version')) {
    loadEnvFile(__DIR__.'/../../.env.version');
}
if ($_ENV['APP_ENVIRONMENT'] ?? $_SERVER['APP_ENVIRONMENT'] ?? '' == 'test') {
    loadEnvFile(__DIR__.'/../../.env.test');
} else if (is_file(__DIR__.'/../../.env')) {
    loadEnvFile(__DIR__.'/../../.env');
}


function loadEnvFile($path)
{
    $content = file_get_contents($path);
    foreach (explode("\n", $content) as $line) {
        $equalIndex = strpos($line, '=');
        if ($equalIndex > 0) {
            $name = trim(substr($line, 0, $equalIndex));
            $value = trim(substr($line, $equalIndex + 1));
            if ($value[0] == "'") {
                $value = substr($value, 1, -1);
            }
            if (!isset($_ENV[$name])) {
                $_ENV[$name] = $value;
            }
        }
    }
}
