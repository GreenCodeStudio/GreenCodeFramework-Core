<?php

namespace Core;


class DefaultErrorController extends \Core\StandardController
{
    function index(int $responseCode)
    {
        $this->addViewString('Error', 'main');
    }

    public function hasPermission(string $methodName)
    {
        return true;
    }
    public function getPageTitle(): string
    {
        return "Error";
    }
}
