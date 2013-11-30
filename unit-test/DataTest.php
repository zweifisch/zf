<?php

use \zf\Data;

class DataTest extends PHPUnit_Framework_TestCase
{

	public function testPushLeft()
	{
		$input = [
			'key' => 1, 2,
			'key2' => 3,
			'key3' => 4, 5,
		];
		$this->assertSame(Data::pushLeft($input), [
			'key' => [1, 2],
			'key2' => [3],
			'key3' => [4, 5]
		]);
	}

	public function testPushRight()
	{
		$input = [
			'key', 'key2' => 1,
			'key3' => 2,
		];
		$this->assertSame(Data::pushRight($input), [
			'key' => 1,
			'key2' => 1,
			'key3' => 2,
		]);
	}
}
