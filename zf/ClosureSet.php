<?php

namespace zf;

class ClosureSet
{
	private $registered;
	private $lookupPath;
	private $context;

	public function __construct($context,$lookupPath)
	{
		$this->context = $context;
		$this->lookupPath = $lookupPath;
	}

	public function __load($closureName)
	{
		$filename = $this->lookupPath.DIRECTORY_SEPARATOR.$closureName.'.php';
		$closure = is_readable($filename) ? require $filename: null;

		if (!$closure)
		{
			throw new \Exception("closure \"$closureName\" not found under \"$this->lookupPath\"");
		}
		elseif (1 === $closure)
		{
			throw new \Exception("invalid closure in \"$filename\", forgot to return the closure?");
		}
		return $closure;
	}

	public function __get($name)
	{
		if(isset($this->registered[$name]))
		{
			$closure = $this->registered[$name];
			$this->registered[$name] = null; #  keep the key in $registered array
			if(is_string($closure))
			{
				$closure = $this->__load($closure);
			}
		}
		else
		{
			$closure = $this->__load($name);
		}
		if (!$closure instanceof \Closure)
		{
			throw new \Exception("invalid closure \"$name\"");
		}
		is_null($this->context) or $closure = $closure->bindTo($this->context);
		return $this->{$name} = $closure;
	}

	public function __call($name, $args=null)
	{
		$closure = isset($this->{$name}) ? $this->{$name} : $this->__get($name);
		if($args)
		{
			$numArgs = count($args);
			return
				(1 == $numArgs ? $closure($args[0]) :
				(2 == $numArgs ? $closure($args[0], $args[1]) :
				(3 == $numArgs ? $closure($args[0], $args[1], $args[2]) : call_user_func_array($closure, $args))));
		}
		return $closure();
	}

	public function delayedCall($name, $args=null)
	{
		$that = $this;
		return function() use ($name, $args, $that){ return $that->__call($name, $args); };
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
