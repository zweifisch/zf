<?php

namespace zf;

use Closure;
use Exception;
use JsonSerializable;

class Laziness implements JsonSerializable
{
	use EventEmitter;

	protected $_container;
	protected $_context;

	public function __construct($array=null, $context=null)
	{
		$this->_container = $array ? $array : [];
		$this->_context = $context ? $context : $this;
	}

	public function __get($key)
	{
		if(array_key_exists($key, $this->_container))
		{
			$closure = $this->_container[$key]->bindTo($this->_context);
			$this->$key = $closure();
			$this->emit('computed', ['key'=>$key, 'value'=>$this->$key]);
			return $this->$key;
		}
		else
		{
			throw new Exception("attribute \"$key\" not found");
		}
	}

	public function __isset($key)
	{
		return isset($this->_container[$key]);
	}

	public function __set($key, $value)
	{
		if ($value instanceof \Closure)
		{
			$this->_container[$key] = $value;
		}
		else
		{
			$this->$key = $value;
		}
	}

	public function jsonSerialize()
	{
		foreach($this->_container as $key=>$_)
		{
			$this->$key;
		}
		return $this;
	}
}
