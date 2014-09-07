<?php

use \zf\App;

class SomeComponent
{
	private $config;

	public function __construct($key, $key2=null)
	{
		$this->config = ['key' => $key];
		if ($key2)
		{
			$this->config['key2'] = $key2;
		}
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
		restore_error_handler();
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
			$component = new SomeComponent('');
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
		$config = ['key'=>'value', 'key2'=>'value2'];
		$this->app->set('components', [
				'sc'=> [
					'class' => 'SomeComponent',
					'constructArgs' => $config,
				],
			]);
		$this->app->register('sc');
		$this->assertSame($config,$this->app->sc->getConfig());
	}

	public function testSetGet()
	{
		$this->app->set('option', [true]);
		$this->assertSame([true], $this->app->get('option'));
	}

	public function testComponentInitialized()
	{
		$called = false;
		$this->app->register('sc', 'SomeComponent', ['key'=>''])->initialized(function($sc) use (&$called) {
			$called = true;
		});
		$this->assertFalse($called);
		$this->app->sc;
		$this->assertTrue($called);
	}
}
