<?php

use \zf\Helper;

class HelperTest extends PHPUnit_Framework_TestCase
{
	public function testRegistered()
	{
		$helper = new Helper(null,'');
		$helper->register('h1',function(){return false;});
		$this->assertTrue($helper->registered('h1'));
		$this->assertFalse($helper->h1());
	}

	public function testRegister()
	{
		$helper = new Helper(null,'');
		$helper->register(['h1','h2','h3']);
		$this->assertTrue($helper->registered('h1'));
		$this->assertTrue($helper->registered('h3'));

		$helper->register(['h4'=> function(){return true;}]);
		$this->assertTrue($helper->h4());
	}
}
