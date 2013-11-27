<?php

namespace zf\components;

use JsonSerializable;

class Params implements JsonSerializable
{

	private $_paramHandlers;
	private $_params;

	public function __construct($request, $paramHandlers)
	{
		$this->_paramHanlers = $paramHandlers;
		$this->_params = $request->params;
	}

	public function __get($key)
	{
		if (isset($this->_params->$key))
		{
			return $this->$key = $this->_paramHanlers->registered($key)
				? $this->_paramHanlers->__call($key, [$this->_params->$key])
				: $this->_params->$key;
		}
	}

	public function jsonSerialize()
	{
		$ret = [];
		foreach ($this->_params as $key => $_)
		{
			$ret[$key] = $this->$key;
		}
		return $ret;
	}

	public function __isset($key)
	{
		return isset($this->_params->$key);
	}
}
