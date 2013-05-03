<?php

namespace zf;

class Helper
{
	private $registered;
	private $binded;
	private $context;

	public function __construct($context)
	{
		$this->context = $context;
	}

	public function __call($name, $args)
	{
		if (!isset($this->bineded[$name]))
		{
			if (isset($this->registered[$name]))
			{
				$this->bineded[$name] = $this->registered[$name]->bindTo($this->context);
				unset($this->registered[$name]);
			}
			else
			{
				throw new \Exception("Helper $name not found");
			}
		}
		return call_user_func_array($this->bineded[$name], $args);
	}

	public function register($name, $closure=null)
	{
		if(is_array($name))
		{
			$helpers = $name;
			if(isset($this->registered))
			{
				$this->registered = array_merge($this->registered, $helpers);
			}
			else
			{
				$this->registered = $helpers;
			}
		}
		else
		{
			$this->registered[$name] = $closure;
		}
	}
}
