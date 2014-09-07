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

	public function testTransform()
	{
		$input = [
			[1, 2, 3],
			[4, 5, 6],
			[7, 8, 9],
		];
		$this->assertSame(Data::transform($input), [
			[1, 4, 7],
			[2, 5, 8],
			[3, 6, 9],
		]);
	}

	public function testZip()
	{
		$this->assertSame(Data::zip([1, 2, 3], [4, 5, 6]),
			[[1,4], [2, 5], [3, 6]]);
	}
}
