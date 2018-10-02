<?php

namespace Core\Console;

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


}
