<?php

namespace zf;

class Helper
{
	private $registered;
	private $binded;
	private $context;
	private $closures;

	public function __construct($context,$closures)
	{
		$this->context = $context;
		$this->closures = $closures;
	}

	public function __call($name, $args)
	{
		$closure = $this->__get($name);
		return call_user_func_array($closure, $args);
	}

	public function __get($name)
	{
		if (!isset($this->binded[$name]))
		{
			if (isset($this->registered[$name]))
			{
				$closure = $this->registered[$name];
				$this->registered[$name] = null; #  keep the key in $registered array
			}
			else
			{
				$closure = $this->closures->get($name, false);
			}
			$this->binded[$name] = $closure->bindTo($this->context);
		}
		return $this->binded[$name];
	}

	public function register($name, $closure=null)
	{
		if(is_array($name))
		{
			foreach($name as $name=>$closure)
			{
				if(is_int($name))
				{
					$this->registered[$closure] = null;
				}
				else
				{
					$this->registered[$name] = $closure;
				}
			}
		}
		else
		{
			$this->registered[$name] = $closure;
		}
	}

	public function registered($name)
	{
		return array_key_exists($name, $this->registered);
	}
}
