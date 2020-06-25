<?php

namespace Core\Routing;

use Core\Exceptions\NotFoundException;
use Core\Repository\IdempodencyKeyRepostory;
use mindplay\annotations\AnnotationCache;
use mindplay\annotations\Annotations;

include_once __DIR__.'/Annotations.php';

class RouterOld
{

    public static function routeConsole($controllerName, $methodName, $args)
    {
        static::routeInternal('Console', $controllerName, $methodName, $args);
    }

    public static function routeInternal($type, $controllerName, $methodName, $args)
    {
        ob_start();
        try {
            $error = null;
            $returned = null;
            if ($_ENV['cached_code']) {
                // $controllerClassName = static::findControllerCached($controllerName, $type);
            } else {
                $controllerClassName = static::findController($controllerName, $type);
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
                    if ($_ENV['debug'] == 'true') {
                        dump($exception);
                    }
                }
            } else
                throw new NotFoundException();
            global $debugArray;
            $output = ob_get_contents();
            ob_end_clean();
            echo json_encode(['data' => $returned, 'error' => $error, 'debug' => $debugArray, 'output' => $output]);

        } catch (NotFoundException $e) {

        }
    }

    /**
     * @param string $name
     * @param string $type
     * @return string
     * @throws NotFoundException
     */
    private static function findController(string $name, string $type = 'Controllers')
    {
        $modules = scandir(__DIR__.'/modules/');
        foreach ($modules as $module) {
            if ($module == '.' || $module == '..') {
                continue;
            }
            $filename = __DIR__.'/../'.$name.'.php';
            if (is_file($filename)) {
                include_once $filename;
                $className = "\\$module\\$type\\$name";
                return $className;
            }
        }
        throw new NotFoundException();
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
        if ($_ENV['debug'] == 'true') {
            $stack = [['file' => $exception->getFile(), 'line' => $exception->getLine()]];
            $stack = array_merge($stack, $exception->getTrace());
            $ret['stack'] = $stack;
        }
        return $ret;
    }

    public static function routeAsyncJob($controllerName, $methodName, $args)
    {
        static::routeInternal('AsyncJobs', $controllerName, $methodName, $args);
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
        Log::Request($url);
        if (substr($url, 0, 5)==='/api/') {
            self::routeApi(substr($url, 5));
            return;
        }
        list($type, $controllerName, $methodName, $args) = self::parseUrl($url);

        if ($type == 'Ajax' && ($_SERVER['HTTP_X_JS_ORIGIN'] ?? '') !== 'true') {
            http_response_code(403);
            return;
        }

        try {
            http_response_code(200);
            list($controllerClassName, $controller) = static::dispatchController($type, $controllerName, $methodName, $args);
            if (!method_exists($controller, $methodName))
                throw new NotFoundException();
            self::initAnnotationsCache();
            $annotations = Annotations::ofMethod($controller, $methodName);
            $canSafeRepeat=false;
            foreach ($annotations as $annotation) {
                if (isset($_SERVER['HTTP_X_JSON']) && is_a($annotation, 'NoAjaxLoaderAnnotation')) {
                    echo json_encode(['needFullReload' => true]);
                    return;
                }
                if (is_a($annotation, 'CanSafeRepeatAnnotation')) {
                    $canSafeRepeat=true;
                }
            }
            if ($type == 'Ajax' &&!$canSafeRepeat){
                $key=$_SERVER['HTTP_X_IDEMPOTENCY_KEY'];
                if(empty($key)||!(new IdempodencyKeyRepostory())->Test($key)){
                    http_response_code(409);
                    echo json_encode(['idempotencyKeyUsed' => true]);
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
                    echo json_encode(['views' => $controller->getViews(), 'breadcrumb' => $controller->getBreadcrumb(), 'title' => $controller->getTitle(), 'debug' => $controller->debugOutput, 'data' => $controller->initInfo, 'error' => null], JSON_PARTIAL_OUTPUT_ON_ERROR);
                } else {
                    $controller->postAction();
                }
            }
        } catch (\Throwable $ex) {
            $responseCode = 500;
            if ($ex instanceof NotFoundException)
                $responseCode = 404;
            else if ($ex instanceof \Authorization\Exceptions\NoPermissionException)
                $responseCode = 403;
            else if ($ex instanceof \Authorization\Exceptions\UnauthorizedException)
                $responseCode = 401;
            else {
                error_log($ex);
                Log::Exception($ex);
            }
            http_response_code($responseCode);
            $debugEnabled = $_ENV['debug'] == 'true';
            dump($ex);
            $debugOutput = ob_get_clean();
            ob_end_clean();
            if ($type == 'Ajax') {
                header('Content-type: application/json');
                global $debugArray;
                echo json_encode(['error' => static::exceptionToArray($ex), 'debug' => $debugEnabled ? $debugArray : [], 'output' => $debugEnabled ? ($controller->debugOutput ?? '') : ''], JSON_PARTIAL_OUTPUT_ON_ERROR);
            } else {
                list($controllerClassNameError, $controllerError) = static::dispatchController('Controllers', 'Error', 'index', [$responseCode, $debugEnabled ? $debugOutput : '']);
                self::runMethod($controllerClassNameError, $controllerError);
                if (isset($_SERVER['HTTP_X_JSON'])) {
                    echo json_encode(['views' => $controllerError->getViews(), 'debug' => $debugEnabled ? $debugOutput : '', 'error' => static::exceptionToArray($ex)], JSON_PARTIAL_OUTPUT_ON_ERROR);
                } else {
                    $controllerError->initInfo->error = static::exceptionToArray($ex);
                    $controllerError->initInfo->code = $responseCode;
                    $controllerError->postAction();
                }
            }

        }
    }

    public static function routeApi($url)
    {
        header('Content-Type: application/json');
        try {
            list($controllerData, $methodData) = static::dispatchApiController($url);
            $controller = new $controllerData->classPath;
            $reflectionMethod = new \ReflectionMethod($controllerData->classPath, $methodData->name);
            $returned = $reflectionMethod->invokeArgs($controller, []);
            echo json_encode($returned);
        }catch (\Throwable $ex) {
            $responseCode = 500;
            if ($ex instanceof NotFoundException)
                $responseCode = 404;
            else if ($ex instanceof \Authorization\Exceptions\NoPermissionException)
                $responseCode = 403;
            else if ($ex instanceof \Authorization\Exceptions\UnauthorizedException)
                $responseCode = 401;
            else {
                error_log($ex);
                Log::Exception($ex);
            }
            http_response_code($responseCode);
    }}

    private static function dispatchApiController($url)
    {
        $list = static::listControllers('Api');
        foreach ($list as $controller) {
            foreach ($controller->methods as $method) {
                foreach ($method->annotations as $annotation) {
                    if ($annotation instanceof \ApiEndpointAnnotation) {
                        if (strtoupper($annotation->type) === $_SERVER['REQUEST_METHOD']) {
                            $template = explode('/', trim($annotation->url, ' /'));
                            $urlArray = explode('/', trim($url, ' /'));
                            if (count($template) == count($urlArray)) {
                                $isMatch = true;
                                for ($i = 0; $i < count($template); $i++) {
                                    if ($template[$i] != ($urlArray[$i] ?? '')) {
                                        $isMatch = false;
                                        break;
                                    }
                                }
                                if ($isMatch) {
                                    return [$controller, $method];
                                }
                            }
                        }
                    }
                }
            }
        }
        throw new NotFoundException();
    }

    public static function listControllers(string $type)
    {
        $ret = [];
        $modules = scandir(__DIR__.'/modules/');
        foreach ($modules as $module) {
            if ($module == '.' || $module == '..') {
                continue;
            }
            if (is_dir(__DIR__.'/../'.$type)) {
                $controllers = scandir(__DIR__.'/../'.$type);
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
            $controllerInfo->module = $module;
            $controllerInfo->name = $name;
            $controllerInfo->methods = [];
            try {
                $classPath = "\\$module\\$type\\$name";
                $controllerInfo->classPath=$classPath;
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

    /**
     * @param $url
     * @return array
     */
    protected static function parseUrl($url): array
    {
        $exploded = explode('/', explode('?', $url)[0]);
        $type = 'Controllers';
        if (($exploded[1] ?? '') == 'ajax') {
            $type = 'Ajax';
            global $debugType;
            $debugType = 'object';

            $controllerName = $exploded[2] ?? '';
            $methodName = $exploded[3] ?? '';
            $args = [];
            foreach ($_POST['args'] ?? [] as $key => $arg) {
                $args[$key] = json_decode($arg, false);
            }
            foreach (self::getFileArgs() as $key => $arg) {
                $args[$key] = $arg;
            }
            ksort($args);
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

    private static function getFileArgs()
    {
        if (empty($_FILES['args']))
            return [];
        $ret = [];
        $keys = array_keys($_FILES['args']['tmp_name']);
        foreach ($keys as $key) {
            $ret[$key] = [
                'type' => $_FILES['args']['type'][$key],
                'tmp_name' => $_FILES['args']['tmp_name'][$key],
                'size' => $_FILES['args']['size'][$key],
                'name' => $_FILES['args']['name'][$key],
                'error' => $_FILES['args']['error'][$key]
            ];
        }
        return $ret;
    }

    static function dispatchController($type, $controllerName, $methodName, $args)
    {
        if ($_ENV['cached_code']) {
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
            throw new NotFoundException();

    }

    public static function findScheduleJobs()
    {
        $controllers = static::listControllers('AsyncJobs');
        $ret = [];
        foreach ($controllers as $controller) {
            foreach ($controller->methods as $method) {
                foreach ($method->annotations as $annotation) {
                    if ($annotation instanceof \ScheduleJobAnnotation)
                        $ret[] = (object)['controller' => $controller->name, 'method' => $method->name];
                }
            }
        }
        return $ret;
    }
}