<?php

namespace zf;

class Config
{
	private $configs = [];

	public function __get($name)
	{
		if (!array_key_exists($name, $this->configs)) throw new \Exception("config key '$name' not found");
		return $this->configs[$name];
	}

	public function load($path)
	{
		if (is_readable($path))
		{
			$this->configs = array_merge($this->configs, require $path);
		}
	}

	public function set($name,$value=null)
	{
		if(1 == func_num_args())
		{
			is_array($name)? $this->multiSet($name) : $this->setBool($name);
		}
		else
		{
			$this->configs[$name] = $value;
		}
	}

	private function multiSet($options)
	{
		foreach($options as $key=>$value)
		{
			is_int($key)? $this->setBool($value) : $this->configs[$key] = $value;
		}
	}

	private function setBool($name)
	{
		strncmp('no', $name, 2)
			? $this->configs[$name] = true
			: $this->configs[substr($name, 2)] = false;
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
