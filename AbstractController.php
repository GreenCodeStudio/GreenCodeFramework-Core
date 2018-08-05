<?php

namespace Core;

abstract class AbstractController
{
    public $initInfo;

    public function __construct()
    {
        $this->initInfo = new \stdClass();
    }

    public function preAction()
    {

    }

    public function postAction()
    {

    }

    public function getInitInfo()
    {
        return $this->initInfo;
    }

    public function hasPermission()
    {
       return \Authorization\Authorization::isLogged();
    }
}
