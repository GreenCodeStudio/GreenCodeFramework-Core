<?php

namespace Core\Console;

use Core\Routing\AjaxRouter;
use Core\Routing\ConsoleRouter;
use Core\Routing\Router;
use Core\Routing\StandardRouter;
use MKrawczyk\FunQuery\FunQuery;

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

    function getControllers()
    {
        return FunQuery::create((new StandardRouter())->listControllers())->map(fn($x) => (object)['name' => $x->name, 'module' => $x->module, 'classPath' => $x->classPath,
            'methods' => FunQuery::create($x->methods)]);
    }

    function getControllerMethods()
    {
        return FunQuery::create((new StandardRouter())->listControllers())->map(fn($x) => FunQuery::create($x->methods)->map(fn($y) => (object)['controller' => $x->name, 'method' => $y->name, 'parameters' => $y->parameters, 'annotations' => $y->annotations]));
    }

    function getAjaxControllers()
    {
        return (new AjaxRouter())->listControllers();
    }
}
