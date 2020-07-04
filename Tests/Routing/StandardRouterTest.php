<?php

include_once __DIR__.'/../../../Core/InitTest.php';

use Core\Routing\StandardRouter;

class StandardRouterTest extends PHPUnit\Framework\TestCase
{
    public function testParseUrl_empty()
    {
        $obj = new StandardRouter();
        $obj->url = "/";
        $obj->parseUrl();

        $this->assertEquals("Start", $obj->controllerName);
        $this->assertEquals("index", $obj->methodName);
        $this->assertEmpty($obj->args);
    }

    public function testParseUrl_full()
    {
        $obj = new StandardRouter();
        $obj->url = "/abc/def/ghi/1/2/3";
        $obj->parseUrl();

        $this->assertEquals("abc", $obj->controllerName);
        $this->assertEquals("def", $obj->methodName);
        $this->assertEquals(["ghi", "1", "2", "3"], $obj->args);
    }
}