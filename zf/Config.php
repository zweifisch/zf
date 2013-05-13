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
			if(is_array($name))
			{
				foreach($name as $key=>$value)
				{
					$this->configs[$key] = $value;
				}
			}
			else
			{
				if(0 == strncmp('no', $name, 2))
				{
					$name = substr($name, 2);
					$this->configs[$name] = false;
				}
				else
				{
					$this->configs[$name] = true;
				}
			}
		}
		else
		{
			$this->configs[$name] = $value;
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
