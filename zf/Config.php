<?php

namespace zf;

class Config
{
	private $configs;

	public function __construct()
	{
		$this->configs = [];
		$this->load('configs.php');
	}

	public function __get($name)
	{
		return $this->configs[$name];
	}

	public function load($path)
	{
		if (is_readable($path))
		{
			$this->configs = array_merge($this->configs, require_once $path);
		}
	}

	public function __set($name, $value)
	{
		$this->configs[$name] = $value;
	}
}
