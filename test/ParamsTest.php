<?php

use \zf\App;
use function helper\get;

class ParamsTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->app = new App('web');
        restore_error_handler();
    }

    public function testParams()
    {
        $request = get($this->app, '/9', ['key'=> 'value']);

        $this->app->get('/:id', function(){
            return $this->params;
        });

        $result = $request->run();

        $this->assertEquals($result->status, 200);
        $this->assertEquals($result->body, json_encode([
            'key'=> 'value',
            'id'=>'9']));
    }
}
