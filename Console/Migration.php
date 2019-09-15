<?php

namespace Core\Console;

use Core\Router;

class Migration extends \Core\AbstractController
{
    function Upgrade()
    {
        $migr = \Core\Migration::factory();
        $migr->upgrade();
    }
    function Read()
    {
        $migr = \Core\Migration::factory();
        return $migr->oldStructureToXml();
    }
}
