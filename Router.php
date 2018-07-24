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
                $type = 'Ajax';
                global $debugType;
                $debugType = 'object';

                $controllerName = $exploded[2] ?? '';
                $methodName = $exploded[3] ?? '';
                $args = [];
                foreach ($_POST['args'] ?? [] as $arg) {
                    $args[] = json_decode($arg, false);
                }
            } else {
                $controllerName = $exploded[1] ?? '';
                $methodName = $exploded[2] ?? '';
                $args = array_slice($exploded, 3);
            }
            $controllerName = preg_replace('/[^a-zA-Z0-9_]/', '', $controllerName);
            $methodName = preg_replace('/[^a-zA-Z0-9_]/', '', $methodName);
            if (empty($controllerName))
                $controllerName = 'Start';
            if (empty($methodName))
                $methodName = 'index';
            if (getenv('cached_code')) {

            } else {
                $controllerClassName = static::findController($controllerName, $type);
            }
            $controller = new $controllerClassName();
            $controller->preAction();
            $error = null;
            $returned = null;
            if (method_exists($controller, $methodName)) {
                try {
                    $reflectionMethod = new \ReflectionMethod($controllerClassName, $methodName);

                    $controller->initInfo->controllerName = $controllerName;
                    $controller->initInfo->methodName = $methodName;
                    $controller->initInfo->methodArguments = array_slice($exploded, 3);
                    $returned = $reflectionMethod->invokeArgs($controller, $args);

                    if (method_exists($controller, $methodName.'_data')) {
                        $reflectionMethodData = new \ReflectionMethod($controllerClassName, $methodName.'_data');
                        $controller->initInfo->data = $reflectionMethodData->invokeArgs($controller, $args);
                    }
                } catch (\Throwable $exception) {
                    $error = static::exceptionToArray($exception);
                    dump($error);
                }
            } else
                throw new \Core\Exceptions\NotFoundException();


            if ($type == 'Ajax') {
                header('Content-type: application/json');
                global $debugArray;
                ob_end_clean();
                echo json_encode(['data' => $returned, 'error' => $error, 'debug' => $debugArray]);
            } else {
                $controller->debugOutput = ob_get_clean();
                if ($_SERVER['HTTP_X_JSON']) {
                    echo json_encode(['views' => $controller->getViews(), 'data' => $controller->initInfo, 'error' => $error]);
                } else {
                    ob_start();
                    $controller->postAction();
                    ob_flush();
                }
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

    private static function exceptionToArray(\Throwable $exception)
    {
        return ['type' => get_class($exception), 'message' => $exception->getMessage(), 'code' => $exception->getCode()];
    }

}
