<?php

use \zf\ClosureSet;

class ClosureSetTest extends PHPUnit_Framework_TestCase
{
	public function testRegistered()
	{
		$this->helper->register('h1',function(){return false;});
		$this->assertTrue($this->helper->registered('h1'));
		$this->assertFalse($this->helper->h1());
	}

	public function testRegister()
	{ 
		$this->helper->register(['h1','h2','h3']);
		$this->assertTrue($this->helper->registered('h1'));
		$this->assertTrue($this->helper->registered('h3'));

		$this->helper->register(['h4'=> function(){return true;}]);
		$this->assertTrue($this->helper->h4());
	}

	public function testLoadClosure()
	{
		$this->assertSame($this->helper->inc(1), 2);
	}

	public function setup()
	{
		$this->helper = new ClosureSet(null, __DIR__ . DIRECTORY_SEPARATOR . '/closures');
	}
}
