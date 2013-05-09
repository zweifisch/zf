<?php

namespace zf;

class Config
{
	private $configs;

	public function __get($name)
	{
		return $this->configs[$name];
	}

	public function load($path)
	{
		if (is_readable($path))
		{
			$this->configs = is_array($this->configs)? array_merge($this->configs, require $path) : require $path;
		}
	}

	public function __isset($name)
	{
		return isset($this->configs[$name]);
	}

	public function __set($name, $value)
	{
		$this->configs[$name] = $value;
	}
}
