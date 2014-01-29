<?php

use zf\Reflection;

class Thing
{
	public function __construct($arg, $arg2=2, $arg3=3)
	{
		$this->args = compact('arg', 'arg2', 'arg3');
	}

	public function getArgs()
	{
		return $this->args;
	}
}

class ReflectionTest extends PHPUnit_Framework_TestCase
{

	public function testInstance()
	{
		$thing = Reflection::Instance('\\Thing', ['arg3'=>4, 'arg'=>1], null);
		$this->assertSame($thing->getArgs(), ['arg'=>1, 'arg2'=>2, 'arg3'=>4]);
	}

	public function testApply()
	{
		$closure = function($param, $param2, $param3=0) {
			return [$param, $param2, $param3];
		};

		$this->assertSame(Reflection::apply($closure, ['param'=>1, 'param2'=>2]), [1,2,0]);
		$this->assertSame(Reflection::apply($closure, [9, 10]), [9,10,0]);
		$this->assertSame(Reflection::apply($closure, ['param3'=>17, 'param'=>7, 'param2'=>1]), [7,1,17]);
	}

	public function testParseDoc()
	{
		/**
		 * @param string $foo more doc
		 * @param string $bar
		 * @permission all
		 */
		$closure = function(){};

		$this->assertSame(Reflection::parseDoc($closure), [
			['param', 'string $foo more doc'],
			['param', 'string $bar'],
			['permission', 'all'],
		]);
	}

	public function testParameters()
	{
		$closure = function($foo, $bar=null) {};
		$this->assertEquals(count(Reflection::parameters($closure)), 2);
	}
}
