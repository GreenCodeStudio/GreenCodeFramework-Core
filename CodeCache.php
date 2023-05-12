<?php
/**
 * Created by PhpStorm.
 * User: matri
 * Date: 30.07.2018
 * Time: 18:45
 */

namespace Core;


use Core\Routing\AjaxRouter;
use Core\Routing\Router;
use Core\Routing\StandardRouter;

class CodeCache
{
    static public function regenerate()
    {

        $data = new \StdClass();
        $data->Controllers = (new StandardRouter())->listControllers();
        $data->Ajax = (new AjaxRouter())->listControllers();
        dump($data);
    }
}