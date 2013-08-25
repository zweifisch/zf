<?php

namespace zf;

class JsonRpcError
{

	public $id;
	public $message;
	public $data;

	public function __construct($id, $message, $data=null)
	{
		$this->id = $id;
		$this->message = $message;
		$this->data = $data;
	}
}
