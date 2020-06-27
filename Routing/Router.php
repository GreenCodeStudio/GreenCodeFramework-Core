<?php


namespace Core\Routing;


use Authorization\Exceptions\NoPermissionException;
use Authorization\Exceptions\UnauthorizedException;
use Core\Exceptions\NotFoundException;
use Core\Log;
use ReflectionMethod;

class Router
{
    public static function routeHttp($url)
    {
        $router = self::getHttpRouter($url);
        try {
            $router->url = $url;
            $router->findController();
            $router->invoke();
        } catch (\Throwable $ex) {
            $router->sendBackException($ex);
            return;
        }
        $router->sendBackSuccess();

    }

    private static function getHttpRouter(string $url): Router
    {
        if (substr($url, 0, 5) === '/api/') {
            return new ApiRouter();
        } else if (substr($url, 0, 6) === '/ajax/') {
            return new AjaxRouter();
        } else {
            if (isset($_SERVER['HTTP_X_JSON'])) {
                return new StandardJsonRouter();
            } else {
                return new StandardRouter();
            }
        }
    }

    protected function sendBackException(\Throwable $ex)
    {
        http_response_code($this->getHttpCode($ex));
        $this->logExceptionIfNeeded($ex);
        dump($ex);
    }

    protected function getHttpCode(\Throwable $ex)
    {
        if ($ex instanceof NotFoundException)
            return 404;
        else if ($ex instanceof NoPermissionException)
            return 403;
        else if ($ex instanceof UnauthorizedException)
            return 401;
        else
            return 500;
    }

    protected function logExceptionIfNeeded(\Throwable $ex)
    {
        if (!($ex instanceof NotFoundException) && !($ex instanceof NoPermissionException) && !($ex instanceof UnauthorizedException)) {
            error_log($ex);
            Log::Exception($ex);
        }
    }

    protected function findControllerClass(string $type = 'Controllers')
    {
        $modulesPath = __DIR__.'/../../../modules';
        $modules = scandir($modulesPath);
        foreach ($modules as $module) {
            if ($module == '.' || $module == '..') {
                continue;
            }
            $filename = $modulesPath.'/'.$module.'/'.$type.'/'.$this->controllerName.'.php';
            if (is_file($filename)) {
                include_once $filename;
                $className = "\\$module\\$type\\$this->controllerName";
                return $className;
            }
        }
        throw new NotFoundException();
    }

    protected function runMethod()
    {
        $reflectionMethod = new ReflectionMethod($this->controllerClassName, $this->controller->initInfo->methodName);
        $this->returned = $reflectionMethod->invokeArgs($this->controller, $this->controller->initInfo->methodArguments);

        if (method_exists($this->controller, $this->controller->initInfo->methodName.'_data')) {
            $reflectionMethodData = new ReflectionMethod($this->controllerClassName, $this->controller->initInfo->methodName.'_data');
            $this->controller->initInfo->data = $reflectionMethodData->invokeArgs($this->controller, $this->controller->initInfo->methodArguments);
        }
    }

    protected function exceptionToArray(\Throwable $exception)
    {
        $ret = ['type' => get_class($exception), 'message' => $exception->getMessage(), 'code' => $exception->getCode()];
        if ($_ENV['debug'] == 'true') {
            $stack = [['file' => $exception->getFile(), 'line' => $exception->getLine()]];
            $stack = array_merge($stack, $exception->getTrace());
            $ret['stack'] = $stack;
        }
        return $ret;
    }
}