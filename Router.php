<?php

namespace Core;

class Router {

    public static function route($url) {
        ob_start();
        try {
            $exploded = explode('/', $url);
            $controllerName = preg_replace('/[^a-zA-Z0-9_]/', '', $exploded[1]);
            if (getenv('cached_code')) {
                
            } else {
                static::findController($controllerName);
            }ob_flush();
        } catch (\Core\Exceptions\NotFoundException $e) {
            http_response_code(404);
            ob_clean();
        }
    }

    private static function findController($name) {
        $modules = scandir(__DIR__.'/../');
        foreach ($modules as $module) {
            if ($module == '.' || $module == '..') {
                continue;
            }
            $filename = __DIR__.'/../'.$module.'/Controllers/'.$name.'.php';
            if (is_file($filename)) {
                include_once $filename;
                $className = "\\$module\\Controllers\\$name";
                return $className;
            }
        }
        throw new \Core\Exceptions\NotFoundException();
    }

}
