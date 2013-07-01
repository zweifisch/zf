<?php

class EventEmitterTest extends PHPUnit_Framework_TestCase
{
	public function testPrioirty()
	{
		$called = [];

		$emitter  = $this->getObjectForTrait('\zf\EventEmitter');

		$emitter->on('test', function() use (&$called){
			$called[] = 2;
		});

		$emitter->on('test', function() use (&$called){
			$called[] = 3;
		})->priority(-1);

		$emitter->on('test', function() use (&$called){
			$called[] = 1;
		})->priority(1);

		$emitter->emit('test');
 
      $this->assertSame($called, [1, 2, 3]);
	}

	public function testFuzzyEmit()
	{
		$called = [];
		$emitter = $this->getObjectForTrait('\zf\EventEmitter');

		$that = $this;

		$emitter->on('test:fuzzy', function($data, $event) use (&$called, $that){
			$that->assertSame($event, 'test:fuzzy');
			$called[] = 1;
		});
		$emitter->on('*:fuzzy', function($data, $event) use (&$called, $that){
			$that->assertSame($event, 'test:fuzzy');
			$called[] = 2;
		});
		$emitter->on('test:*', function($data, $event) use (&$called, $that){
			$that->assertSame($event, 'test:fuzzy');
			$called[] = 3;
		});
		$emitter->on('fuzzy:*', function() use (&$called){
			$called[] = 4;
		});
		$emitter->on('*:test', function() use (&$called){
			$called[] = 5;
		});

		$emitter->emit('test:fuzzy');
      $this->assertSame($called, [1, 3, 2]);
	}

	public function testStop()
	{
		$called = [];

		$emitter  = $this->getObjectForTrait('\zf\EventEmitter');

		$emitter->on('test', function() use (&$called){
			$called[] = 1;
			return true;
		});

		$emitter->on('test', function() use (&$called){
			$called[] = 1;
		});

		$emitter->emit('test');
 
      $this->assertSame($called, [1]);
	}

	public function testEmit()
	{
		$called = [];

		$emitter  = $this->getObjectForTrait('\zf\EventEmitter');

		$emitter->on('test', function() use (&$called){
			$called[] = 1;
		});

		$emitter->on('test', function() use (&$called){
			$called[] = 1;
		});

		$emitter->emit('test');
 
      $this->assertSame($called, [1,1]);
	}
}

