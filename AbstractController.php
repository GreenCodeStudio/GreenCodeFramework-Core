<?php

namespace Core;

use Authorization\Exceptions\NoPermissionException;

abstract class AbstractController
{
    public $initInfo;
    public $debugOutput = '';

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
        return ($_ENV['debug'] ?? '') == 'true';
    }

    public function hasPermission(string $methodName)
    {
        return true;
    }

    public function can(string $group, string $permission):bool
    {
        return \Authorization\Authorization::getUserData()->permissions->can($group, $permission);
    }

    /**
     * @throws NoPermissionException
     */
    public function will(string $group, string $permission)
    {
        if (!$this->can($group, $permission))
            throw new NoPermissionException();
    }
    public function redirect(string $url)
    {
        http_response_code(301);
        header("location: $url");
    }
}
