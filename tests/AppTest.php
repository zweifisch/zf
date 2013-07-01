<?php

use \zf\App;

class SomeComponent
{
	private $config;

	public function __construct($config=null)
	{
		$this->config = $config;
	}

	public function config($config)
	{
		$this->config = $config;
	}

	public function getConfig()
	{
		return $this->config;
	}
}

class AppTest extends PHPUnit_Framework_TestCase
{

	public function setUp()
	{
		$this->app = new App;
	}

	public function testHelper()
	{
		$this->app->helper('yes', function(){return true;});
		$this->assertTrue($this->app->helper->registered('yes'));
		$this->assertTrue($this->app->helper->yes());
		$this->assertTrue($this->app->yes());
	}

	public function testRegisterComponentUsingClosure()
	{
		$config = ['key'=>'value'];
		$this->app->register('sc', function() use ($config){
			$component = new SomeComponent;
			$component->config($config);
			return $component;
		});
		$this->assertSame($config,$this->app->sc->getConfig());
	}

	public function testRegisterComponent()
	{
		$config = ['key'=>'value'];
		$this->app->register('sc', 'SomeComponent', $config);
		$this->assertSame($config,$this->app->sc->getConfig());
	}

	public function testRegisterComponentFromConfig()
	{
		$config = ['key'=>'value'];
		$this->app->set('sc', $config);
		$this->app->register('sc', 'SomeComponent');
		$this->assertSame($config,$this->app->sc->getConfig());
	}

	public function testSetGet()
	{
		$this->app->set('option', [true]);
		$this->assertSame([true], $this->app->get('option'));
	}
}
