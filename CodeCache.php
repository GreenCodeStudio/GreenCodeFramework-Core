<?php
/**
 * Created by PhpStorm.
 * User: matri
 * Date: 30.07.2018
 * Time: 18:45
 */

namespace Core;


class CodeCache
{
    static public function regenerate()
    {

        $data = new \StdClass();
        $data->Controllers = Router::listControllers('Controllers');
        $data->Ajax = Router::listControllers('Ajax');
        dump($data);
    }
}