<?php

use \zf\CliRouter;

class CliRouterTest extends PHPUnit_Framework_TestCase
{

	public function setUp()
	{
		$this->router = new CliRouter;
	}

	public function testStaticPattern()
	{
		$this->router->append('cmd','ls',['cb0']);
		$this->router->append('cmd','rm',['cb1']);

		list($cb,$params) = $this->router->dispatch(['ls']);
		$this->assertEquals($cb, ['cb0']);
		$this->assertSame($params, []);

		list($cb,$params) = $this->router->dispatch(['rm']);
		$this->assertEquals($cb, ['cb1']);
		$this->assertSame($params, []);

		list($cb,$params) = $this->router->dispatch(['cd']);
		$this->assertEquals($cb, null);
		$this->assertSame($params, null);
	}

	public function testPositionalParams()
	{
		$this->router->append('cmd','show <id>',['cb0']);

		list($cb,$params) = $this->router->dispatch(['show']);
		$this->assertEquals($cb, null);
		$this->assertSame($params, null);

		list($cb,$params) = $this->router->dispatch(['show', '12']);
		$this->assertEquals($cb, ['cb0']);
		$this->assertSame($params, ['id'=>'12']);
	}

	public function testOptions()
	{
		$this->router->append('cmd','list <path>',['cb0']);
		$this->router->options(['offset'=>'0', 'limit'=>'20', 'verbose']);

		list($cb,$params) = $this->router->dispatch(['list', '/var/log']);
		$this->assertEquals($cb, ['cb0']);
		$this->assertSame($params, ['offset'=>'0', 'limit'=>'20', 'verbose'=>false, 'path'=>'/var/log']);

		list($cb,$params) = $this->router->dispatch(['list', '--verbose', '--offset=100', '/var/log']);
		$this->assertEquals($cb, ['cb0']);
		$this->assertSame($params, ['offset'=>'100', 'limit'=>'20', 'verbose'=>true, 'path'=>'/var/log']);
	}

}

