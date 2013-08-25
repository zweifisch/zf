<?php

namespace zf;

use Exception;

class JsonRpc
{

	private $version = '2.0';

	private $codes = [
		-32700 => 'Parse error',
		-32600 => 'Invalid Request',
		-32601 => 'Method not found',
		-32602 => 'Invalid params',
		-32603 => 'Internal error',
	];

	private $batch = false;

	public function __construct($errorCodes=null)
	{
		if($errorCodes)
		{
			$this->codes += $errorCodes;
		}
	}

	public function error($code, $data=null)
	{
		if(!isset($this->codes[$code]))
		{
			throw new Exception("Error Code '$code' not defined");
		}
		return new JsonRpcError($code, $this->codes[$code], $data);
	}

	public function methodNotFound()
	{
		return $this->error(-32601);
	}

	public function parse($request)
	{
		if(!$request || !is_array($request))
		{
			$this->result(null, $this->error(-32700));
			return false;
		}

		if(is_assoc($request))
		{
			$this->calls[] = $this->parseSingleCall($request);
		}
		else
		{
			$this->batch = true;
			foreach($request as $call)
			{
				$this->calls[] = $this->parseSingleCall($call);
			}
		}

		if(empty($this->calls))
		{
			$this->result(null, $this->error(-32600));
		}
		return !empty($this->calls);
	}

	public function parseSingleCall($call)
	{
		if(empty($call['method']) || empty($call['jsonrpc'])) return $this->error(-32600);
		if($call['jsonrpc'] != $this->version) return $this->error(-32600);

		return [
			$call['method'],
			isset($call['params']) ? $call['params'] : [],
			isset($call['id']) ? $call['id'] : null,
		];
	}

	public function result($id, $result)
	{
		$this->results[] = $result instanceof JsonRpcError
			? ['jsonrpc'=> $this->version, 'error'=> $result, 'id'=> $id]
			: ['jsonrpc'=> $this->version, 'result'=> $result, 'id'=> $id];
	}

	public function response()
	{
		return $this->batch ? $this->results : current($this->results);
	}
}

