<?php

use \zf\FancyObject;

class FancyObjectTest extends PHPUnit_Framework_TestCase
{

	public function testJson()
	{
		$this->assertEquals(json_encode($this->fancy), json_encode($this->source));
	}

	public function testFancy()
	{
		$this->assertSame($this->fancy->key->asStr(), 'str');
		$this->assertSame($this->fancy->key2->key3->key4->asInt(), 2);
		$this->assertSame($this->fancy->key2->key5->asNum(), 1.2);
		$this->assertSame($this->fancy->asArray(), $this->source);
	}

	public function testObject()
	{
		$source = json_decode(json_encode($this->source));
		$fancy = new FancyObject($source, $this->validators, $this->mappers);
		$this->assertSame($fancy->asRaw(), $source);
	}

	public function testDefault()
	{
		$this->assertSame($this->fancy->key0->asStr('default'), 'default');
		$this->assertSame($this->fancy->key1->key2->asInt(2), 2);
		$this->assertSame($this->fancy->key3->key4->asNum(1.1), 1.1);
		$this->assertSame($this->fancy->key5->asArray([1,2]), [1,2]);
		$this->assertSame($this->fancy->key->asStr('rts'), 'str');
	}

	public function testValidator()
	{
		$triggered = false;
		$that = $this;
		$this->fancy->on(zf\EVENT_VALIDATION_ERROR, function($data) use (&$triggered, $that){
			$triggered = true;
			$that->assertSame($data['key'], 'key');
		});
		$value = $this->fancy->key->minlen(4)->asStr();
		$this->assertNull($value);
		$this->assertTrue($triggered);

		$value = $this->fancy->key->minlen(3)->asStr();
		$this->assertSame($value, 'str');
	}

	public function testNumericValidator()
	{
		$triggered = false;
		$that = $this;
		$this->fancy->on(zf\EVENT_VALIDATION_ERROR, function($data) use (&$triggered, $that){
			$triggered = true;
			$that->assertSame($data['key'], 'key2.key5');
		});
		$value = $this->fancy->key2->key5->min(4)->asNum();
		$this->assertNull($value);
		$this->assertTrue($triggered);

		$value = $this->fancy->key2->key5->asNum();
		$this->assertSame($value, 1.2);
	}

	public function testRegexpValidator()
	{
		$triggered = false;
		$that = $this;
		$this->fancy->on(zf\EVENT_VALIDATION_ERROR, function($data) use (&$triggered, $that){
			$triggered = true;
			$that->assertSame($data['key'], 'key');
		});
		$value = $this->fancy->key->match('/@/')->asStr();
		$this->assertNull($value);
		$this->assertTrue($triggered);

		$value = $this->fancy->email->match('/@/')->asStr();
		$this->assertSame($value, 'vali.d@ema.il');
	}

	public function testSanitize()
	{
		// $value = $this->fancy->str->sanitize('trim')->asStr();
		$value = $this->fancy->str->asStr();
		$this->assertSame($value, 'str');
	}

	public function testRequired()
	{
		$fancy = new FancyObject([], $this->validators, $this->mappers);
		$failed = false;
		$that = $this;
		$fancy->on(zf\EVENT_VALIDATION_ERROR, function($message) use (&$failed, $that){
			$failed = true;
			$that->assertSame(json_encode($message), json_encode(['validator'=>'required', 'input'=>null, 'key'=>'key0.key1']));
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

	public function testExtract()
	{
		$result = $this->fancy->extract([
			'key' => 'minlen:3',
		]);
		$expected = ['key'=>'str'];
		$this->assertSame($expected, $result);

		$result = $this->fancy->extract([
			'key' => 'minlen:4',
		]);
		$this->assertNull($result);

		$result = $this->fancy->extract([
			'key' => 'minlen:3',
			'key2.key5',
		]);
		$expected = ['key'=>'str', 'key2.key5'=>'1.2'];
		$this->assertSame($expected, $result);

		$result = $this->fancy->key2->extract([
			'key3.key4:Int' => 'min:1',
			'key5:Num',
		]);
		$expected = ['key3.key4'=>2, 'key5'=>1.2];
		$this->assertSame($expected, $result);
	}

	public function testExtractDefault()
	{
		$result = $this->fancy->extract([
			'key3' => 'default:defaultval|minlen:5',
		]);
		$expected = ['key3'=>'defaultval'];
		$this->assertSame($expected, $result);
	}

	public function setup()
	{
		$this->source = [
			'key'=>'str',
			'key2'=>[
				'key3'=>['key4'=>'2'],
				'key5'=>'1.2'
			],
			'email'=>'vali.d@ema.il',
			'str'=>' str  ',
		];
		$this->validators = new \zf\ClosureSet(null,'');
		$this->validators->register(require __DIR__.'/../zf/validators.php');
		$this->mappers = new \zf\ClosureSet(null, '');
		$this->mappers->register(require __DIR__.'/../zf/mappers.php');
		$this->fancy = new FancyObject($this->source, $this->validators, $this->mappers);
	}
}
