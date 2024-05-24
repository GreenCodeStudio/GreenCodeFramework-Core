<?php


namespace Core\Routing;


use Authorization\Authorization;
use Authorization\Exceptions\NoPermissionException;
use Authorization\Exceptions\UnauthorizedException;
use CanSafeRepeatAnnotation;
use Core\Exceptions\NotFoundException;
use Core\Log;
use Core\Repository\IdempodencyKeyRepostory;
use Core\IJsonSerializable;
use mindplay\annotations\AnnotationCache;
use mindplay\annotations\Annotations;
use MKrawczyk\FunQuery\FunQuery;
use ReflectionMethod;

class Router
{
    protected $controller;

    protected ?string $methodName;

    protected ?string $controllerName;
    public mixed $returned;
    public $url;
    public array $args;
    public string $controllerClassName;

    public static function routeHttp($url)
    {
        Log::Request($url);
        setDumpDebugType('text', false);
        header('x-version: '.($_ENV['VERSION'] ?? '-'));
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

    public static function getHttpRouter(string $url): Router
    {
        if (substr($url, 0, 5) === '/api/') {
            return new ApiRouter();
        } else if (substr($url, 0, 6) === '/ajax/') {
            return new AjaxRouter();
        } else {
            setDumpDebugType('text', false);
            if (isset($_SERVER['HTTP_X_JSON'])) {
                return new StandardJsonRouter();
            } else {
                return new StandardRouter();
            }
        }
    }

    protected function invoke()
    {
        ob_start();
        $this->runMethod();
        $debug = ob_get_contents();
        ob_get_clean();
        if (!empty($debug))
            dump($debug);
    }

    protected function runMethod()
    {
        $reflectionMethod = new ReflectionMethod($this->controllerClassName, $this->controller->initInfo->methodName);
        if (!empty($_SERVER['HTTP_X_IDEMPOTENCY_KEY']) && $_SERVER['HTTP_X_IDEMPOTENCY_KEY'] != 'null') {
            if (!$this->canSafeRepeat($this->controllerClassName, $this->controller->initInfo->methodName)) {
                if (!(new IdempodencyKeyRepostory())->Test($_SERVER['HTTP_X_IDEMPOTENCY_KEY'])) {
                    throw new \Exception('Idempodency key used');
                }
            }
        }
        $parameters = $reflectionMethod->getParameters();
        foreach ($this->controller->initInfo->methodArguments as $i => &$value) {
            $parameter = $parameters[$i] ?? null;
            if ($parameter != null) {
                $type = $parameter->getType();
                if ($type != null) {
                    $typeName = $type->getName();
                    $value = $this->convertType($typeName, $value);
                }
            }

        }
        $minimumParameters = FunQuery::create($reflectionMethod->getParameters())->filter(fn($x) => !$x->isDefaultValueAvailable())->count();

        if (count($this->controller->initInfo->methodArguments) < $minimumParameters)
            throw new NotFoundException('Not enough parameters');

        $this->returned = $reflectionMethod->invokeArgs($this->controller, $this->controller->initInfo->methodArguments);

        if (method_exists($this->controller, $this->controller->initInfo->methodName.'_data')) {
            $reflectionMethodData = new ReflectionMethod($this->controllerClassName, $this->controller->initInfo->methodName.'_data');
            $this->controller->initInfo->data = $reflectionMethodData->invokeArgs($this->controller, $this->controller->initInfo->methodArguments);
        }
    }

    private function convertType($type, $value)
    {
        if ($type == 'DateTime') {
            return new \DateTime($value);
        } else {
            return $value;
        }
    }

    private function canSafeRepeat($className, $methodName)
    {
        self::initAnnotationsCache();
        $annotations = Annotations::ofMethod($className, $methodName);
        foreach ($annotations as $annotation) {
            if ($annotation instanceof CanSafeRepeatAnnotation)
                return true;
        }
        return false;
    }

    protected static function initAnnotationsCache(): void
    {
        if (empty(Annotations::$config['cache']))
            Annotations::$config['cache'] = new AnnotationCache(__DIR__.'/../../../cache');
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

    public static function routeConsole($controllerName, $methodName, $args)
    {
        $router = new ConsoleRouter();
        try {
            $router->controllerName = $controllerName;
            $router->methodName = $methodName;
            $router->args = $args;
            $router->findController();
            $router->invoke();
        } catch (\Throwable $ex) {
            $router->sendBackException($ex);
            return;
        }
        $router->sendBackSuccess();
    }

    public static function routeAsyncJob($controllerName, $methodName, $args)
    {
        $router = new AsyncJobRouter();

        try {
            $router->controllerName = $controllerName;
            $router->methodName = $methodName;
            $router->args = $args;
            $router->findController();
            $router->invoke();
        } catch (\Throwable $ex) {
            $router->sendBackException($ex);
            return;
        }
        $router->sendBackSuccess();
    }

    public function listControllers()
    {
        $ret = [];
        $modules = scandir(__DIR__.'/../../');
        foreach ($modules as $module) {
            if ($module == '.' || $module == '..') {
                continue;
            }
            if (is_dir(__DIR__.'/../../'.$module.'/'.$this->controllerType)) {
                $controllers = scandir(__DIR__.'/../../'.$module.'/'.$this->controllerType);
                foreach ($controllers as $controllerFile) {
                    if (!is_dir(__DIR__.'/../../'.$module.'/'.$this->controllerType.'/'.$controllerFile)) {
                        $info = $this->getControllerInfo($module, $controllerFile);
                        if ($info != null) {
                            $ret[$info->name] = $info;
                        }
                    }
                }
            }
        }
        return $ret;
    }

    function getControllerInfo($module, $controllerFile): ?object
    {
        self::initAnnotationsCache();
        if (preg_match('/^(.*)\.php$/', $controllerFile, $matches)) {
            $name = $matches[1];
            $controllerInfo = new \StdClass();
            $controllerInfo->module = $module;
            if (substr($name, -strlen($this->controllerType)) == $this->controllerType) {
                $controllerInfo->name = substr($name, 0, -strlen($this->controllerType));
            } else {
                $controllerInfo->name = $name;
            }
            $controllerInfo->methods = [];
            try {
                $classPath = "\\$module\\$this->controllerType\\$name";
                $controllerInfo->classPath = $classPath;
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

    public function parseUrl()
    {
        $exploded = explode('/', explode('?', $this->url)[0]);
        $controllerName = empty($exploded[1]) ? 'Start' : $exploded[1];
        $methodName = empty($exploded[2]) ? 'index' : $exploded[2];
        $this->args = FunQuery::create(array_slice($exploded, 3))->map(fn($x) => urldecode($x))->toArray();
        $this->controllerName = preg_replace('/[^a-zA-Z0-9_]/', '', $controllerName);
        $this->methodName = preg_replace('/[^a-zA-Z0-9_]/', '', $methodName);
    }

    protected function exceptionToArray(\Throwable $exception)
    {
        $ret = ['type' => get_class($exception), 'message' => $exception->getMessage(), 'code' => $exception->getCode()];
        if ($exception instanceof IJsonSerializable) {
            $ret = array_merge($ret, $exception->toJsonableObject());
        }
        if ($_ENV['debug'] == 'true') {
            $stack = [['file' => $exception->getFile(), 'line' => $exception->getLine()]];
            $stack = array_merge($stack, $exception->getTrace());
            $ret['stack'] = $stack;
        }
        return $ret;
    }

    protected function prepareController()
    {
        if ($_ENV['cached_code']) {
            // $controllerClassName = static::findControllerCached($controllerName, $type);
        } else {
            $this->controllerClassName = $this->findControllerClass();
        }
        $this->controller = new $this->controllerClassName();
        if (!$this->controller->hasPermission($this->methodName)) {
            if (Authorization::isLogged()) {
                throw new NoPermissionException();
            } else {
                throw new UnauthorizedException();
            }
        }
        $this->controller->preAction();
    }

    protected function findControllerClass()
    {
        $type = $this->controllerType;
        $suffix = $type;
        if ($type == 'Controllers') {
            $suffix = 'Controller';
        }
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
            $filename = $modulesPath.'/'.$module.'/'.$type.'/'.$this->controllerName.$suffix.'.php';
            if (is_file($filename)) {
                include_once $filename;
                $className = "\\$module\\$type\\{$this->controllerName}$suffix";
                return $className;
            }
        }
        throw new NotFoundException();
    }

    protected function prepareMethod()
    {
        if (!method_exists($this->controller, $this->methodName)) {
            throw new NotFoundException();
        }
        if (!isset($this->controller->initInfo)) {
            $this->controller->initInfo = new \stdClass();
        }
        $this->controller->initInfo->controllerName = $this->controllerName;
        $this->controller->initInfo->methodName = $this->methodName;
        $this->controller->initInfo->methodArguments = $this->args;
    }
}
