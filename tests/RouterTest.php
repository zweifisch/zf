<?php

use \zf\Router;

class RouterTest extends PHPUnit_Framework_TestCase
{

	public function setUp()
	{
		$this->router = new Router;
	}

	public function testStaticPattern()
	{
		$this->router->append('GET',['/1','cb1']);
		$this->router->append('GET',['/2','cb2']);

		list($cb,$params) = $this->router->dispatch('GET','/1');
		$this->assertEquals($cb, 'cb1');
		$this->assertSame($params, null);

		list($cb,$params) = $this->router->dispatch('GET','/2');
		$this->assertEquals($cb, 'cb2');

		list($cb,$params) = $this->router->dispatch('GET','/3');
		$this->assertEquals($cb, null);
	}

	public function testParams()
	{
		$this->router->append('GET',['/1/:foo/:bar/2','cb1']);

		list($cb,$params) = $this->router->dispatch('GET','/1/2/3/4');
		$this->assertSame($cb, null);
		$this->assertSame($params, null);

		list($cb,$params) = $this->router->dispatch('GET','/1/2/3/2');
		$this->assertSame($cb, 'cb1');
		$this->assertSame($params, ['foo'=>'2', 'bar'=>'3']);
	}

	public function testOptinalParams()
	{
		$this->router->append('GET',['/0/:foo?', 'cb0']);
		$this->router->append('GET',['/1/:foo?/:bar?/2', 'cb1']);
		$this->router->append('GET',['/2/:foo?/3/:bar?', 'cb2']);

		list($cb,$params) = $this->router->dispatch('GET','/0');
		$this->assertSame($cb, 'cb0');
		$this->assertSame($params, ['foo'=>null]);
		return;

		list($cb,$params) = $this->router->dispatch('GET','/0/f');
		$this->assertSame($cb, 'cb0');
		$this->assertSame($params, ['foo'=>'f']);

		list($cb,$params) = $this->router->dispatch('GET','/1/2/3/2');
		$this->assertSame($cb, 'cb1');
		$this->assertSame($params, ['foo'=>'2', 'bar'=>'3']);

		list($cb,$params) = $this->router->dispatch('GET','/1/2/2');
		$this->assertSame($cb, 'cb1');
		$this->assertSame($params, ['foo'=>'2','bar'=>null]);

		list($cb,$params) = $this->router->dispatch('GET','/1/2');
		$this->assertSame($cb, 'cb1');
		$this->assertSame($params, ['foo'=>null,'bar'=>null]);

		list($cb,$params) = $this->router->dispatch('GET','/1');
		$this->assertSame($cb, null);
		$this->assertSame($params, null);

		list($cb,$params) = $this->router->dispatch('GET','/2/3');
		$this->assertSame($cb, 'cb2');
		$this->assertSame($params, ['foo'=>null, 'bar'=>null]);

		list($cb,$params) = $this->router->dispatch('GET','/2/f/3');
		$this->assertSame($cb, 'cb2');
		$this->assertSame($params, ['foo'=>'f', 'bar'=>null]);

		list($cb,$params) = $this->router->dispatch('GET','/2/f/3/b');
		$this->assertSame($cb, 'cb2');
		$this->assertSame($params, ['foo'=>'f', 'bar'=>'b']);

		list($cb,$params) = $this->router->dispatch('GET','/2/f/4/b');
		$this->assertSame($cb, null);
		$this->assertSame($params, null);
	}
}
