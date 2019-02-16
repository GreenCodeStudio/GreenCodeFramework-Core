<?php

namespace Core\Console;

use Core\Router;

class System extends \Core\AbstractController
{

    function demo($a, int $b = 5, \Exception $c = null)
    {
        dump($a, $b);
    }

    function migration()
    {
        $migr = new \Core\Migration();
        $migr->upgrade();
    }

    function server()
    {
        return $_SERVER;
    }

    function addDemo(int $a, int $b)
    {
        echo 'Dodawanie czas zacząć';
        return $a + $b;
    }

    function GetMethods(string $type = 'Console')
    {
        $methods = [];
        $controllers = Router::listControllers($type);
        foreach ($controllers as $controller) {
            if (!empty($controller->methods)) {
                foreach ($controller->methods as $method) {
                    $method->controllerName = $controller->name;
                    $methods[] = $method;
                }
            }
        }
        return $methods;
    }

}
