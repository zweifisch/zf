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

	public function testBulkSet()
	{
		$config = new Config;
		$config->set(['key'=>'value', 'nopretty', 'fancy']);
		$this->assertSame($config->key, 'value');
		$this->assertFalse($config->pretty);
		$this->assertTrue($config->fancy);
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

	/**
  	 * @expectedException Exception
	 */
	public function testException()
	{
		$config = new Config;
		$this->config->ENV;
	}

	/**
  	 * @expectedException Exception
	 */
	public function testLoadException()
	{
		$config = new Config;
		$config->load('config.php');
	}
}
