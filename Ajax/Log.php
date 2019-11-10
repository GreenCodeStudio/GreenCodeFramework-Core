<?php

namespace Core\Ajax;
use Common\PageAjaxController;

class Log extends PageAjaxController
{
    public function addFrontError($event)
    {
        \Core\Log::FrontException($event);
    }
}