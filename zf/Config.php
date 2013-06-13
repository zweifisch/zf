<?php

namespace zf;

class Config
{
	private $_configs = [];

	public function __get($name)
	{
		if(array_key_exists($name, $this->_configs))
		{
			return $this->_configs[$name];
		}
		throw new \Exception("config key \"$name\" not found");
	}

	public function load($path, $quiet=false)
	{
		if(stream_resolve_include_path($path))
		{
			$this->_configs = array_merge($this->_configs, require $path);
		}
		else
		{
			if(!$quiet) throw new \Exception("config \"$path\" not loaded");
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
			$this->_configs[$name] = $value;
		}
	}

	private function multiSet($options)
	{
		foreach($options as $key=>$value)
		{
			is_int($key)? $this->setBool($value) : $this->_configs[$key] = $value;
		}
	}

	private function setBool($name)
	{
		strncmp('no', $name, 2)
			? $this->_configs[$name] = true
			: $this->_configs[substr($name, 2)] = false;
	}

	public function __isset($name)
	{
		return isset($this->_configs[$name]);
	}

	public function __set($name, $value)
	{
		$this->_configs[$name] = $value;
	}
}
