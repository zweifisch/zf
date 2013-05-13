<?php

use \zf\Config;

class ConfigTest extends PHPUnit_Framework_TestCase
{

	public function testSet()
	{
		$config = new Config;
		$config->set('key', 'value');
		$this->assertSame($config->key, 'value');
	}

	public function testBuilSet()
	{
		$config = new Config;
		$config->set(['key'=>'value']);
		$this->assertSame($config->key, 'value');
	}

	public function testBoolenOption()
	{
		$config = new Config;
		$config->set('nofancy');
		$this->assertFalse($config->fancy);
		$config->set('pretty');
		$this->assertTrue($config->pretty);
	}

	public function testIsset()
	{
		$config = new Config;
		$config->set('key', 'value');
		$this->assertTrue(isset($config->key));
		$this->assertFalse(isset($config->value));
	}
}
