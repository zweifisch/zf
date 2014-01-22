<?php

use \zf\components\CliRouter;

class CliRequest
{
	public $argv;

	function __construct($argv)
	{
		$this->argv = $argv;
	}
}

class CliRouterTest extends PHPUnit_Framework_TestCase
{

	public function testStaticPattern()
	{
		$router = new CliRouter(new CliRequest(['ls']));
		$router->append('cmd','ls',['cb0']);
		$router->append('cmd','rm',['cb1']);

		list($cb, $params) = $router->dispatch();
		$this->assertEquals($cb, ['cb0']);
		$this->assertSame($params, []);

		$router = new CliRouter(new CliRequest(['rm']));
		$router->append('cmd','ls',['cb0']);
		$router->append('cmd','rm',['cb1']);

		list($cb, $params) = $router->dispatch();
		$this->assertEquals($cb, ['cb1']);
		$this->assertSame($params, []);

		$router = new CliRouter(new CliRequest(['cd']));
		$router->append('cmd','ls',['cb0']);
		$router->append('cmd','rm',['cb1']);

		list($cb, $params) = $router->dispatch();
		$this->assertEquals($cb, null);
		$this->assertSame($params, null);
	}

	public function testPositionalParams()
	{
		$router = new CliRouter(new CliRequest(['show']));
		$router->append('cmd','show <id>',['cb0']);

		list($cb,$params) = $router->dispatch();
		$this->assertEquals($cb, null);
		$this->assertSame($params, null);

		$router = new CliRouter(new CliRequest(['show', '12']));
		$router->append('cmd','show <id>',['cb0']);

		list($cb,$params) = $router->dispatch();
		$this->assertEquals($cb, ['cb0']);
		$this->assertSame($params, ['id'=>'12']);
	}

	public function testOptions()
	{
		$router = new CliRouter(new CliRequest(['list', '/var/log']));
		$router->append('cmd','list <path>',['cb0']);
		$router->options(['offset'=>'0', 'limit'=>'20', 'verbose']);

		list($cb,$params) = $router->dispatch();
		$this->assertEquals($cb, ['cb0']);
		$this->assertSame($params, ['offset'=>'0', 'limit'=>'20', 'verbose'=>false, 'path'=>'/var/log']);

		$router = new CliRouter(new CliRequest(['list', '--verbose', '--offset=100', '/var/log']));
		$router->append('cmd','list <path>',['cb0']);
		$router->options(['offset'=>'0', 'limit'=>'20', 'verbose']);

		list($cb,$params) = $router->dispatch();
		$this->assertEquals($cb, ['cb0']);
		$this->assertSame($params, ['offset'=>'100', 'limit'=>'20', 'verbose'=>true, 'path'=>'/var/log']);
	}

}

