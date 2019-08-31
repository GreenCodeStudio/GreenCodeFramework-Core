<?php

namespace Core\Console;

use Core\Router;

class System extends \Core\AbstractController
{
    function Upgrade()
    {
        $migr = \Core\Migration::factory();
        $migr->upgrade();
    }

}
