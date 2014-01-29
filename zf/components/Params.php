<?php

namespace zf\components;

use JsonSerializable;

class Params implements JsonSerializable
{

	private $_paramHandlers;
	private $_params;
	private $_enabledParams;

	public function __construct($router, $paramHandlers)
	{
		$this->_paramHanlers = $paramHandlers;
		$this->_params = $_GET ? array_merge($_GET, $router->params) : $router->params;
	}

	public function __get($key)
	{
		if (isset($this->_params[$key]))
		{
			return $this->$key = $this->_paramHanlers->registered($key)
				? $this->_paramHanlers->__call($key, [$this->_params[$key]])
				: $this->_params[$key];
		}
	}

	public function _swap($key, $callable)
	{
		$this->_params[$key] = $callable($this->_params[$key]);
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
		return isset($this->_params[$key]);
	}

	public function _enabled($key)
	{
		return !empty($this->_enabledParams[$key]);
	}

	public function _enable($key)
	{
		$this->_enabledParams[$key] = true;
	}
}
