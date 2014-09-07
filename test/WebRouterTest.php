<?php

use \zf\components\WebRouter;

class WebRequest
{
	function __construct($method, $path)
	{
		$this->method = $method;
		$this->path = $path;
		$this->segments = explode('/', substr($path, 1));
	}
}

class WebRouterTest extends PHPUnit_Framework_TestCase
{

	public function testStaticPattern()
	{

		$router = new WebRouter(new WebRequest('GET', '/'));
		$router->append('GET','/',['cb0']);
		$router->append('GET','/1',['cb1']);
		$router->append('GET','/2',['cb2']);

		list($cb,$params) = $router->dispatch();
		$this->assertEquals($cb, ['cb0']);
		$this->assertSame($params, null);

		$router = new WebRouter(new WebRequest('GET', '/1'));
		$router->append('GET','/',['cb0']);
		$router->append('GET','/1',['cb1']);
		$router->append('GET','/2',['cb2']);

		list($cb,$params) = $router->dispatch();
		$this->assertEquals($cb, ['cb1']);
		$this->assertSame($params, null);

		$router = new WebRouter(new WebRequest('GET','/2'));
		$router->append('GET','/',['cb0']);
		$router->append('GET','/1',['cb1']);
		$router->append('GET','/2',['cb2']);

		list($cb,$params) = $router->dispatch();
		$this->assertEquals($cb, ['cb2']);


		$router = new WebRouter(new WebRequest('GET','/3'));
		$router->append('GET','/',['cb0']);
		$router->append('GET','/1',['cb1']);
		$router->append('GET','/2',['cb2']);

		list($cb,$params) = $router->dispatch();
		$this->assertEquals($cb, null);
	}

	public function testParams()
	{
		$router = new WebRouter(new WebRequest('GET','/1/2/3/4'));
		$router->append('GET','/1/:foo/:bar/2',['cb1']);
		$router->append('GET','/:foo',['cb2']);

		list($cb,$params) = $router->dispatch();
		$this->assertSame($cb, null);
		$this->assertSame($params, null);

		$router = new WebRouter(new WebRequest('GET','/1/2/3/2'));
		$router->append('GET','/1/:foo/:bar/2',['cb1']);
		$router->append('GET','/:foo',['cb2']);

		list($cb,$params) = $router->dispatch();
		$this->assertSame($cb, ['cb1']);
		$this->assertSame($params, ['foo'=>'2', 'bar'=>'3']);

		$router = new WebRouter(new WebRequest('GET','/1'));
		$router->append('GET','/1/:foo/:bar/2',['cb1']);
		$router->append('GET','/:foo',['cb2']);

		list($cb,$params) = $router->dispatch();
		$this->assertSame($cb, ['cb2']);
		$this->assertSame($params, ['foo'=>'1']);
	}

	private function factory($method, $path, $rules)
	{
		$router = new WebRouter(new WebRequest($method, $path));
		$router->bulk($rules);
		return $router->dispatch();
	}

	public function testOptinalParams()
	{
		$rules = [
			['GET','/0/:foo?', ['cb0']],
			['GET','/1/:foo?/:bar?/2', ['cb1']],
			['GET','/2/:foo?/3/:bar?', ['cb2']],
		];

		list($cb,$params) = $this->factory('GET', '/0', $rules);
		$this->assertSame($cb, ['cb0']);
		$this->assertSame($params, ['foo'=>null]);

		list($cb,$params) = $this->factory('GET', '/0/f', $rules);
		$this->assertSame($cb, ['cb0']);
		$this->assertSame($params, ['foo'=>'f']);

		list($cb,$params) = $this->factory('GET', '/1/2/3/2', $rules);
		$this->assertSame($cb, ['cb1']);
		$this->assertSame($params, ['foo'=>'2', 'bar'=>'3']);

		list($cb,$params) = $this->factory('GET','/1/2/2', $rules);
		$this->assertSame($cb, ['cb1']);
		$this->assertSame($params, ['foo'=>'2','bar'=>null]);

		list($cb,$params) = $this->factory('GET','/1/2', $rules);
		$this->assertSame($cb, ['cb1']);
		$this->assertSame($params, ['foo'=>null,'bar'=>null]);

		list($cb,$params) = $this->factory('GET','/1', $rules);
		$this->assertSame($cb, null);
		$this->assertSame($params, null);

		list($cb,$params) = $this->factory('GET','/2/3', $rules);
		$this->assertSame($cb, ['cb2']);
		$this->assertSame($params, ['foo'=>null, 'bar'=>null]);

		list($cb,$params) = $this->factory('GET','/2/f/3', $rules);
		$this->assertSame($cb, ['cb2']);
		$this->assertSame($params, ['foo'=>'f', 'bar'=>null]);

		list($cb,$params) = $this->factory('GET','/2/f/3/b', $rules);
		$this->assertSame($cb, ['cb2']);
		$this->assertSame($params, ['foo'=>'f', 'bar'=>'b']);

		list($cb,$params) = $this->factory('GET','/2/f/4/b', $rules);
		$this->assertSame($cb, null);
		$this->assertSame($params, null);
	}
}
