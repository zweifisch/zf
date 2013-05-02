<?php

namespace zf;

class Helper
{
	private $registeredHelpers;

	function __call($name, $args)
	{
		if(isset($this->registeredHelpers[$name]))
		{
			return call_user_func_array($this->registeredHelpers[$name]->bindTo(App::getApp()), $args);
		}
		else
		{
			throw new \Exception("Helper $name not found");
		}
	}

	function register($name, $closure=null)
	{
		if(is_array($name))
		{
			$helpers = $name;
			if(isset($this->registeredHelpers))
			{
				$this->registeredHelpers = array_merge($this->registeredHelpers, $helpers);
			}
			else
			{
				$this->registeredHelpers = $helpers;
			}
		}
		else
		{
			$this->registeredHelpers[$name] = $closure;
		}
	}
}
