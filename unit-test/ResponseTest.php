<?php

use \zf\App;

class ResponseTest extends PHPUnit_Framework_TestCase
{
	public function testRenderAsString()
	{
		$app = new App;
		$app->set('views', __DIR__.DIRECTORY_SEPARATOR.'views');
		// $this->assertSame('1 + 2 = 3', $app->renderAsString('index',['a'=>1,'b'=>2,'c'=>3]));
	}
}
