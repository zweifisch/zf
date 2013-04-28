<?php

namespace zf;

class Config
{
	private $__configs;
	public function __get($name)
	{
		if(!is_array($this->__configs))
		{
			$this->__configs = [];
			$this->load('configs.php');
		}
		return $this->__configs[$name];
	}

	public function load($path)
	{
		if (is_readable($path))
		{
			$this->__configs = array_merge($this->__configs, require_once $path);
		}
	}
}
