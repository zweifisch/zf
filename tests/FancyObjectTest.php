<?php

use \zf\FancyObject;

class FancyObjectTest extends PHPUnit_Framework_TestCase
{

	/**
	 * @dataProvider provider
	 */
	public function testJson($fancy, $source)
	{
		$this->assertEquals(json_encode($fancy), json_encode($source));
	}

	/**
	 * @dataProvider provider
	 */
	public function testFancy($fancy, $source)
	{
		$this->assertSame($fancy->key->asStr(), 'str');
		$this->assertSame($fancy->key2->key3->key4->asInt(), 2);
		$this->assertSame($fancy->key2->key5->asNum(), 1.2);
		$this->assertSame($fancy->asArray(), $source);
	}

	/**
	 * @dataProvider provider
	 */
	public function testDefault($fancy)
	{
		$this->assertSame($fancy->key0->asStr('default'), 'default');
		$this->assertSame($fancy->key1->key2->asInt(2), 2);
		$this->assertSame($fancy->key3->key4->asNum(1.1), 1.1);
		$this->assertSame($fancy->key5->asArray([1,2]), [1,2]);
		$this->assertSame($fancy->key->asStr('rts'), 'str');
	}

	public function testRequired()
	{
		$fancy = new FancyObject([]);
		$failed = false;
		$that = $this;
		$fancy->on('validation:failed', function($message) use (&$failed, $that){
			$failed = true;
			$that->assertEquals($message, ['validator'=>'required', 'input'=>'key0.key1']);
		});
		$this->assertNull($fancy->key0->key1->asInt());
		$this->assertTrue($failed);
	}

	/**
	 * @expectedException Exception
	 */
	public function testException()
	{
		$fancy = new FancyObject([]);
		$fancy->asNull();
	}

	public function provider()
	{
		$source = ['key'=>'str','key2'=>['key3'=>['key4'=>'2'], 'key5'=>'1.2']];
		return [[new FancyObject($source), $source]];
	}

}
