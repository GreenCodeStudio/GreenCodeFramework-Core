<?php

namespace Core;


use Common\PageStandardController;

class DefaultErrorController extends PageStandardController
{
    function index(int $responseCode)
    {
        $this->addViewString('Error', 'main');
    }

    public function hasPermission(string $methodName)
    {
        return true;
    }
}
