<?php

namespace Core;

class Router
{

    public static function route($url)
    {
        ob_start();
        try {
            $exploded = explode('/', $url);
            $controllerName = preg_replace('/[^a-zA-Z0-9_]/', '', $exploded[1] ?? '');
            $methodName = preg_replace('/[^a-zA-Z0-9_]/', '', $exploded[2] ?? '');
            if (empty($controllerName))
                $controllerName = 'Start';
            if (empty($methodName))
                $methodName = 'index';
            if (getenv('cached_code')) {

            } else {
                $controllerClassName = static::findController($controllerName);
                $controller = new $controllerClassName();
                $reflectionMethod = new \ReflectionMethod($controllerClassName, $methodName);
                $controller->preAction();
                if (method_exists($controller, $methodName)) {
                    $controller->initInfo->controllerName = $controllerName;
                    $controller->initInfo->methodName = $methodName;
                    $controller->initInfo->methodArguments = array_slice($exploded, 3);
                    $reflectionMethod->invokeArgs($controller, array_slice($exploded, 3));

                    if (method_exists($controller, $methodName.'_data')) {
                        $reflectionMethodData = new \ReflectionMethod($controllerClassName, $methodName.'_data');
                        $controller->initInfo->data = $reflectionMethodData->invokeArgs($controller, array_slice($exploded, 3));
                    }
                } else
                    throw new \Core\Exceptions\NotFoundException();
                $controller->debugOutput = ob_get_clean();
                ob_start();
                $controller->postAction();
            }
            ob_flush();
        } catch (\Core\Exceptions\NotFoundException $e) {
            http_response_code(404);
            ob_clean();
        }
    }

    private static function findController($name)
    {
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
