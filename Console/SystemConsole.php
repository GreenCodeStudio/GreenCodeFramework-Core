<?php

namespace Core\Console;

use Core\Routing\ConsoleRouter;
use Core\Routing\Router;

class SystemConsole extends \Core\AbstractController
{
    function server()
    {
        return $_SERVER;
    }

    function GetMethods()
    {
        $methods = [];
        $controllers = (new ConsoleRouter())->listControllers();
        foreach ($controllers as $controller) {
            if (!empty($controller->methods)) {
                foreach ($controller->methods as $method) {
                    $method->controllerName = $controller->name;
                    foreach ($method->parameters as &$parameter) {
                        $name = $parameter->name;
                        $isOptional = $parameter->isOptional();
                        $defaultValue = $isOptional ? $parameter->getDefaultValue() : NULL;
                        $parameter = new \stdClass();
                        $parameter->name = $name;
                        $parameter->defaultValue = $defaultValue;
                        $parameter->isOptional = $isOptional;
                    }
                    $methods[] = $method;
                }
            }
        }
        return $methods;
    }
}
