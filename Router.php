<?php

namespace Core;

use mindplay\annotations\AnnotationCache;
use mindplay\annotations\Annotations;

include __DIR__.'/Annotations.php';

class Router
{

    public static function listControllers(string $type)
    {
        $ret = [];
        $modules = scandir(__DIR__.'/../');
        foreach ($modules as $module) {
            if ($module == '.' || $module == '..') {
                continue;
            }
            if (is_dir(__DIR__.'/../'.$module.'/'.$type)) {
                $controllers = scandir(__DIR__.'/../'.$module.'/'.$type);
                foreach ($controllers as $controllerFile) {
                    $info = self::getControllerInfo($type, $module, $controllerFile);
                    if ($info != null) {
                        $ret[$info->name] = $info;
                    }
                }

            }
        }
        return $ret;
    }

    static function getControllerInfo($type, $module, $controllerFile): ?object
    {
        self::initAnnotationsCache();
        if (preg_match('/^(.*)\.php$/', $controllerFile, $matches)) {
            $name = $matches[1];
            $controllerInfo = new \StdClass();
            $controllerInfo->name = $name;
            $controllerInfo->methods = [];
            try {
                $classPath = "\\$module\\$type\\$name";
                $classReflect = new \ReflectionClass($classPath);
                $methods = $classReflect->getMethods();
                foreach ($methods as $methodReflect) {
                    if (!$methodReflect->isPublic()) continue;
                    if ('\\'.$methodReflect->class != $classPath) continue;
                    $methodInfo = new \StdClass();
                    $annotations = Annotations::ofMethod($classPath, $methodReflect->getName());
                    $methodInfo->name = $methodReflect->getName();
                    $methodInfo->parameters = $methodReflect->getParameters();
                    $methodInfo->annotations = $annotations;
                    $controllerInfo->methods[$methodReflect->getName()] = $methodInfo;
                }
            } catch (\Throwable $ex) {
                return null;
            }
            return $controllerInfo;
        }
        return null;
    }

    protected static function initAnnotationsCache(): void
    {
        if (empty(Annotations::$config['cache']))
            Annotations::$config['cache'] = new AnnotationCache(__DIR__.'/../../cache');
    }

    public static function routeConsole($controllerName, $methodName, $args)
    {
        ob_start();
        try {
            $error = null;
            $returned = null;
            if (getenv('cached_code')) {
                // $controllerClassName = static::findControllerCached($controllerName, $type);
            } else {
                $controllerClassName = static::findController($controllerName, 'Console');
            }
            $controller = new $controllerClassName();
            if (method_exists($controller, $methodName)) {
                try {

                    $controller->initInfo->controllerName = $controllerName;
                    $controller->initInfo->methodName = $methodName;
                    $controller->initInfo->methodArguments = $args;
                    $returned = static::runMethod($controllerClassName, $controller);
                } catch (\Throwable $exception) {
                    $error = static::exceptionToArray($exception);
                    if (getenv('debug') == 'true') {
                        dump($exception);
                    }
                }
            } else
                throw new \Core\Exceptions\NotFoundException();
            global $debugArray;
            $output = ob_get_contents();
            ob_end_clean();
            echo json_encode(['data' => $returned, 'error' => $error, 'debug' => $debugArray, 'output' => $output]);

        } catch (\Core\Exceptions\NotFoundException $e) {

        }
    }

    /**
     * @param string $name
     * @param string $type
     * @return string
     * @throws Exceptions\NotFoundException
     */
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

    /**
     * @param string $controllerClassName
     * @param $controller
     * @return mixed
     * @throws \ReflectionException
     */
    protected static function runMethod(string $controllerClassName, $controller)
    {
        $reflectionMethod = new \ReflectionMethod($controllerClassName, $controller->initInfo->methodName);
        $returned = $reflectionMethod->invokeArgs($controller, $controller->initInfo->methodArguments);

        if (method_exists($controller, $controller->initInfo->methodName.'_data')) {
            $reflectionMethodData = new \ReflectionMethod($controllerClassName, $controller->initInfo->methodName.'_data');
            $controller->initInfo->data = $reflectionMethodData->invokeArgs($controller, $controller->initInfo->methodArguments);
        }
        return $returned;
    }

    private static function exceptionToArray(\Throwable $exception)
    {
        $ret = ['type' => get_class($exception), 'message' => $exception->getMessage(), 'code' => $exception->getCode()];
        if (getenv('debug') == 'true') {
            $stack = [['file' => $exception->getFile(), 'line' => $exception->getLine()]];
            $stack = array_merge($stack, $exception->getTrace());
            $ret['stack'] = $stack;
        }
        return $ret;
    }
    /**
     * @param $url
     * @throws Exceptions\NotFoundException
     * @throws \Authorization\Exceptions\NoPermissionException
     * @throws \ReflectionException
     */
    public static function route($url)
    {
        ob_start();

        list($type, $controllerName, $methodName, $args) = self::parseUrl($url);

        if ($type == 'Ajax' && ($_SERVER['HTTP_X_JS_ORIGIN'] ?? '') !== 'true') {
            http_response_code(403);
            return;
        }
        if (!empty($_POST['args'])) {
            $args = array_merge($args, $_POST['args']);
        }
        try {
            http_response_code(200);
            list($controllerClassName, $controller) = static::dispatchController($type, $controllerName, $methodName, $args);
            self::initAnnotationsCache();
            $annotations = Annotations::ofMethod($controller, $methodName);
            foreach ($annotations as $annotation) {
                if (isset($_SERVER['HTTP_X_JSON']) && is_a($annotation, 'NoAjaxLoaderAnnotation')) {
                    echo json_encode(['needFullReload' => true]);
                    return;
                }
            }
            $returned = self::runMethod($controllerClassName, $controller);
            $controller->debugOutput = ob_get_clean();
            ob_end_clean();
            if ($type == 'Ajax') {
                header('Content-type: application/json');
                global $debugArray;
                echo json_encode(['data' => $returned, 'error' => null, 'debug' => $debugArray, 'output' => $controller->debugOutput], JSON_PARTIAL_OUTPUT_ON_ERROR);
            } else {
                if (isset($_SERVER['HTTP_X_JSON'])) {
                    echo json_encode(['views' => $controller->getViews(), 'breadcrumb' => $controller->getBreadcrumb(), 'debug' => $controller->debugOutput, 'data' => $controller->initInfo, 'error' => null], JSON_PARTIAL_OUTPUT_ON_ERROR);
                } else {
                    $controller->postAction();
                }
            }
        } catch (\Throwable $ex) {
            $responseCode = 500;
            if ($ex instanceof \Core\Exceptions\NotFoundException)
                $responseCode = 404;
            else if ($ex instanceof \Authorization\Exceptions\NoPermissionException)
                $responseCode = 403;
            else if ($ex instanceof \Authorization\Exceptions\UnauthorizedException)
                $responseCode = 401;
            http_response_code($responseCode);
            error_log($ex);
            $debugEnabled = getenv('debug') == 'true';
            dump($ex);
            $debugOutput = ob_get_clean();
            ob_end_clean();
            if ($type == 'Ajax') {
                header('Content-type: application/json');
                global $debugArray;
                echo json_encode(['error' => static::exceptionToArray($ex), 'debug' => $debugEnabled ? $debugArray : [], 'output' => $debugEnabled ? ($controller->debugOutput ?? '') : ''], JSON_PARTIAL_OUTPUT_ON_ERROR);
            } else {
                if (isset($_SERVER['HTTP_X_JSON'])) {
                    echo json_encode(['debug' => $debugEnabled ? $debugOutput : '', 'error' => static::exceptionToArray($ex)], JSON_PARTIAL_OUTPUT_ON_ERROR);
                } else {
                    list($controllerClassNameError, $controllerError) = static::dispatchController('Controllers', 'Error', 'index', [$debugEnabled ? $debugOutput : '']);
                    self::runMethod($controllerClassNameError, $controllerError);
                    $controllerError->initInfo->error = static::exceptionToArray($ex);
                    $controllerError->initInfo->code = $responseCode;
                    $controllerError->postAction();
                }
            }

        }
    }

    /**
     * @param $url
     * @return array
     */
    protected static function parseUrl($url): array
    {
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
        return array($type, $controllerName, $methodName, $args);
    }

    static function dispatchController($type, $controllerName, $methodName, $args)
    {
        if (getenv('cached_code')) {
            // $controllerClassName = static::findControllerCached($controllerName, $type);
        } else {
            $controllerClassName = static::findController($controllerName, $type);
        }
        $controller = new $controllerClassName();
        if (!$controller->hasPermission($methodName)) {
            if (\Authorization\Authorization::isLogged()) {
                throw new \Authorization\Exceptions\NoPermissionException();
            } else {
                return self::dispatchController($type, 'Authorization', 'index', []);
            }
        }
        $controller->preAction();
        $returned = null;
        if (method_exists($controller, $methodName)) {


            $controller->initInfo->controllerName = $controllerName;
            $controller->initInfo->methodName = $methodName;
            $controller->initInfo->methodArguments = $args;
            return [$controllerClassName, $controller];
        } else
            throw new \Core\Exceptions\NotFoundException();

    }

}
