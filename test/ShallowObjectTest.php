<?php

use \zf\components\ShallowObject;

class ShallowObjectTest extends PHPUnit_Framework_TestCase
{

    public function testJsonEncode()
    {
        $obj = new ShallowObject(['key' => 'value']);
        $this->assertEquals(json_encode($obj), json_encode(['key'=> 'value']));
    }
}
