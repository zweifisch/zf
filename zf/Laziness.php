<?php

namespace zf;

class Laziness
{
	protected $container;

	public function __construct($array=null)
	{
		$this->container = $array ? $array : [];
	}

	public function __get($name)
	{
		if (isset($this->container[$name]))
		{
			if ($this->container[$name] instanceof \Closure)
			{
				$closure = $this->container[$name]->bindTo($this);
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
			throw new \Exception("attribute $name not found");
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
}
