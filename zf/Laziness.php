<?php

namespace zf;

class Laziness implements \JsonSerializable
{
	protected $container;
	protected $context;

	public function __construct($array=null, $context=null)
	{
		$this->container = $array ? $array : [];
		$this->context = $context ? $context : $this;
	}

	public function __get($name)
	{
		if (array_key_exists($name, $this->container))
		{
			if ($this->container[$name] instanceof \Closure)
			{
				$closure = $this->container[$name]->bindTo($this->context);
				$this->container[$name] = $closure();
				return $this->container[$name];
			}
			else
			{
				return $this->container[$name];
			}
		}
		else
		{
			throw new \Exception("attribute \"$name\" not found");
		}
	}

	public function __isset($name)
	{
		return isset($this->container[$name]);
	}

	public function __set($name, $value)
	{
		$this->container[$name] = $value;
	}

	public function getAll()
	{
		return $this->container;
	}

	public function jsonSerialize()
	{
		foreach($this->container as $key=>$value)
		{
			if($value instanceof \Closure)
			{
				$binded = $value->bindTo($this->context);
				$this->container[$key] = $binded();
			}
		}
		return $this->container;
	}
}
