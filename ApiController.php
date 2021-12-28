<?php

namespace Core;

class ApiController extends AbstractController
{
    protected function getBaseURL(){
        return ($_SERVER['HTTPS']?"https":"http")."://".$_SERVER['HTTP_HOST']."/api/";
    }
}
