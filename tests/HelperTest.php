<?php

use \zf\Helper;

class HelperTest extends PHPUnit_Framework_TestCase
{
	public function testRegistered()
	{
		$helper = new Helper(null,'');
		$helper->register('h1',function(){return true;});
		$this->assertTrue($helper->registered('h1'));
		$this->assertTrue($helper->h1());
	}

	public function testNumericArray()
	{
		$helper = new Helper(null,'');
		$helper->register(['h1','h2','h3']);
		$this->assertTrue($helper->registered('h1'));
		$this->assertTrue($helper->registered('h3'));
	}

	public function testAssocArray()
	{
		$helper = new Helper(null,'');
		$helper->register(['h1'=> function(){return true;}]);
		$this->assertTrue($helper->h1());
	}
}
