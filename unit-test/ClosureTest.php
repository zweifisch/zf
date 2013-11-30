<?php

use \zf\Closure;

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

class ClosureTest extends PHPUnit_Framework_TestCase
{

	public function testInstance()
	{
		$thing = Closure::Instance('\\Thing', ['arg3'=>4, 'arg'=>1], null);
		$this->assertSame($thing->getArgs(), ['arg'=>1, 'arg2'=>2, 'arg3'=>4]);
	}
}
