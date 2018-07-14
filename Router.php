<?php

namespace Core;

class Router
{

    public static function route($url)
    {
        ob_start();
        try {
            $exploded = explode('/', $url);
            $type = 'Controllers';
            if ($exploded[1] == 'ajax') {
                array_splice($exploded, 1, 1);
                $type = 'Ajax';
            }
            $controllerName = preg_replace('/[^a-zA-Z0-9_]/', '', $exploded[1] ?? '');
            $methodName = preg_replace('/[^a-zA-Z0-9_]/', '', $exploded[2] ?? '');
            if (empty($controllerName))
                $controllerName = 'Start';
            if (empty($methodName))
                $methodName = 'index';
            if (getenv('cached_code')) {

            } else {
                $controllerClassName = static::findController($controllerName, $type);
            }
            $controller = new $controllerClassName();
            $reflectionMethod = new \ReflectionMethod($controllerClassName, $methodName);
            $controller->preAction();
            if (method_exists($controller, $methodName)) {
                $controller->initInfo->controllerName = $controllerName;
                $controller->initInfo->methodName = $methodName;
                $controller->initInfo->methodArguments = array_slice($exploded, 3);
                $returned = $reflectionMethod->invokeArgs($controller, array_slice($exploded, 3));

                if (method_exists($controller, $methodName.'_data')) {
                    $reflectionMethodData = new \ReflectionMethod($controllerClassName, $methodName.'_data');
                    $controller->initInfo->data = $reflectionMethodData->invokeArgs($controller, array_slice($exploded, 3));
                }
            } else
                throw new \Core\Exceptions\NotFoundException();


            if ($type == 'Ajax') {
                header('Content-type: application/json');
                echo json_encode(['data' => $returned]);
            } else {
                $controller->debugOutput = ob_get_clean();
                ob_start();
                $controller->postAction();
                ob_flush();
            }
        } catch (\Core\Exceptions\NotFoundException $e) {
            http_response_code(404);
            ob_clean();
        }
    }

    private static function findController(string $name, string $type = 'Controllers')
    {
        $modules = scandir(__DIR__.'/../');
        foreach ($modules as $module) {
            if ($module == '.' || $module == '..') {
                continue;
            }
            $filename = __DIR__.'/../'.$module.'/'.$type.'/'.$name.'.php';
            if (is_file($filename)) {
                include_once $filename;
                $className = "\\$module\\$type\\$name";
                return $className;
            }
        }
        throw new \Core\Exceptions\NotFoundException();
    }

}
