<?php

use zf\JsonRpc;
use zf\JsonRpcError;

class JsonRpcTest extends PHPUnit_Framework_TestCase
{

	public function setup()
	{
		$this->rpc = new JsonRpc([-32009 => 'error']);
	}

	public function testParse()
	{
		$input = ['params'=>['p1'=>'value'], 'method'=>'m1', 'jsonrpc'=>'2.0'];
		$this->rpc->parse($input);
		$this->assertSame($this->rpc->calls, [['m1',['p1'=>'value'],null]]);
	}

	public function testParseFailed()
	{
		$input = ['params'=>['p1'=>'value'], 'jsonrpc'=>'2.0'];
		$this->rpc->parse($input);
		$this->assertSame($this->rpc->calls[0]->code, -32600);
	}

	public function testParseBatch()
	{
		$input = [
			['params'=>['p1'=>'value'], 'method'=>'m1', 'jsonrpc'=>'2.0'],
			['params'=>['p1'=>'value'], 'method'=>'m2', 'jsonrpc'=>'2.0'],
		];
		$this->rpc->parse($input);
		$this->assertSame($this->rpc->calls, [
			['m1',['p1'=>'value'],null],
			['m2',['p1'=>'value'],null],
		]);
	}

}
