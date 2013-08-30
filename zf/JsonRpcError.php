<?php

namespace zf;

class JsonRpcError
{

	public $code;
	public $message;
	public $data;

	public function __construct($code, $message, $data=null)
	{
		$this->code = $code;
		$this->message = $message;
		$this->data = $data;
	}
}
