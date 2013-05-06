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
				unset($this->registered[$name]);
			}
			else
			{
				if (!($closure = $this->getClosure($this->path, $name, false)))
				{
					throw new \Exception("Helper $name not found");
				}
			}
			$this->binded[$name] = $closure->bindTo($this->context);
		}
		return $this->binded[$name];
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
