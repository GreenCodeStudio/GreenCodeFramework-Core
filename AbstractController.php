<?php

namespace Core;

use Authorization\Exceptions\NoPermissionException;

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

    public function isDebug()
    {
        return (getenv('debug') ?? '') == 'true';
    }

    public function hasPermission()
    {
        return \Authorization\Authorization::isLogged();
    }

    public function can(string $group, string $permission)
    {
        return \Authorization\Authorization::getUserData()->permissions->can($group, $permission);
    }

    /**
     * @throws NoPermissionException
     */
    public function will(string $group, string $permission)
    {
        if(!$this->can($group,$permission))
            throw new NoPermissionException();
    }
}
