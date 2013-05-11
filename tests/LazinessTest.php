<?php

use \zf\Laziness;

class LazinessTest extends PHPUnit_Framework_TestCase
{
	public function testGet()
	{
		$object = new Laziness;
		$object->rand = function(){
			return mt_rand();
		};

		$rand = $object->rand;

		$this->assertEquals($rand, $object->rand);
	}

	/**
	 * @expectedException Exception
	 */
	public function testException()
	{
		$object = new Laziness;
		$object->missing;
	}

	public function testJson()
	{
		$object = new Laziness;
		$rand = mt_rand();
		$object->nums = [1,2,3];
		$object->rand = function() use ($rand){
			return $rand;
		};
		$this->assertEquals(json_encode($object), json_encode(['nums'=>[1,2,3], 'rand'=>$rand]));
	}

	public function testIsset()
	{
		$object = new Laziness;
		$this->assertFalse(isset($object->attr));
		$object->attr = null;
		$this->assertFalse(isset($object->attr));
		$object->attr = false;
		$this->assertTrue(isset($object->attr));
	}
}
