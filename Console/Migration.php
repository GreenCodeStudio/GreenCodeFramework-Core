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

}
