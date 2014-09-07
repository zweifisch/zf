<?php

use \zf\App;
use function helper\get;

class QueryTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->app = new App('web');
        restore_error_handler();
    }

    public function testHelper()
    {
        $request = get($this->app, '/', ['key'=> 'value']);

        $this->app->get('/', function(){
            return $this->query;
        });

        $result = $request->run();

        $this->assertEquals($result->status, 200);
        $this->assertEquals($result->body, json_encode(['key'=> 'value']));
    }
}
