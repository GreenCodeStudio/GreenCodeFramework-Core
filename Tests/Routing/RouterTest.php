<?php
include_once __DIR__ . '/../../../Core/InitTest.php';


class RouterTest extends PHPUnit\Framework\TestCase
{
    public function testgetHttpRouter_ajax()
    {
        $obj = \Core\Routing\Router::getHttpRouter("/ajax/controller/method");

        $this->assertInstanceOf("Core\Routing\AjaxRouter", $obj);
    }

    public function testgetHttpRouter_api()
    {
        $obj = \Core\Routing\Router::getHttpRouter("/api/something");

        $this->assertInstanceOf("Core\Routing\ApiRouter", $obj);
    }
}