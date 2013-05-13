<?php

namespace zf;

class Helper
{
	use Closure;

	private $registered;
	private $binded;
	private $context;
	private $path;

	public function __construct($context,$path)
	{
		$this->context = $context;
		$this->path = $path;
	}

	public function __call($name, $args)
	{
		$closure = $this->__get($name);
		return $this->callClosure(null, $closure, null, $args);
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
				$closure = $this->getClosure($this->path, $name, false);
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
