<?php

use \zf\App;

class AppTest extends PHPUnit_Framework_TestCase
{

	public function setUp()
	{
		$this->app = new App;
	}

	public function testHelper()
	{
		$app = new App;
		$app->helper('yes', function(){return true;});
		$this->assertTrue($app->helper->registered('yes'));
		$this->assertTrue($app->helper->yes());
		$this->assertTrue($app->yes());
	}
}
