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

	public function __get($name)
	{
		if(array_key_exists($name, $this->_container))
		{
			if($this->_container[$name] instanceof Closure)
			{
				$closure = $this->_container[$name]->bindTo($this->_context);
				$this->_container[$name] = $closure();
				$this->emit('computed', ['key'=>$name, 'value'=>$this->_container[$name]]);
			}
			return $this->_container[$name];
		}
		else
		{
			throw new Exception("attribute \"$name\" not found");
		}
	}

	public function __isset($name)
	{
		return isset($this->_container[$name]);
	}

	public function __set($name, $value)
	{
		$this->_container[$name] = $value;
	}

	public function jsonSerialize()
	{
		foreach($this->_container as $key=>$value)
		{
			if($value instanceof Closure)
			{
				$binded = $value->bindTo($this->_context);
				$this->_container[$key] = $binded();
			}
		}
		return $this->_container;
	}
}
