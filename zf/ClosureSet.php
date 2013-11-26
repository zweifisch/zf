<?php

namespace zf;

use Exception;

class ClosureSet
{
	private $_registered;
	private $_lookupPath;
	private $_context;
	public $delayed;
	public $_fullname;

	public function __construct($context, $path, $closures = null)
	{
		$this->_context = $context;
		$this->_lookupPath = $path;
		$this->delayed = new _Delayed($this);
		if($closures) $this->register($closures);
	}

	private function _getPath($fullname)
	{
		return $this->_lookupPath.DIRECTORY_SEPARATOR.$fullname;
	}

	private function _getFullname($append)
	{
		return ($this->_fullname ? $this->_fullname . DIRECTORY_SEPARATOR : '') . $append;
	}

	private function _load($fullname)
	{
		$filename = $this->_getPath(str_replace(['.','/'], DIRECTORY_SEPARATOR, $fullname) . '.php');
		$this->_fullname = null;
		$closure = stream_resolve_include_path($filename) ? require $filename: null;
		if(!$closure)
		{
			throw new Exception("closure '$fullname' not found under '$this->_lookupPath'");
		}
		elseif (1 === $closure)
		{
			throw new Exception("invalid closure in '$filename', forgot to return the closure?");
		}
		return $closure;
	}

	public function __get($name)
	{
		if(isset($this->_registered[$name]))
		{
			$closure = $this->_registered[$name];
			$this->_registered[$name] = null; #  keep the key in $_registered array
			if(is_string($closure))
			{
				$closure = $this->_load($closure);
			}
			else if(!$closure instanceof \Closure)
			{
				throw new Exception("invalid closure \"$name\"");
			}
		}
		else
		{
			$name = $this->_getFullname($name);
			if(is_dir($this->_getPath($name, true)))
			{
				$this->_fullname = $name;
				return  $this;
			}
			$closure = $this->_load($name);
		}
		if($this->_context)
		{
			$closure = $closure->bindTo($this->_context);
		}
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

	public function __apply($name, $args)
	{
		return $this->__call($name, Data::is_assoc($args) ? Closure::keywordArgs($this->$name, $args) : $args);
	}

	public function register($name, $closure=null)
	{
		if(is_array($name))
		{
			foreach($name as $name=>$closure)
			{
				is_int($name)
					? $this->_registered[$ret[] = $closure] = null
					: $this->_registered[$ret[] = $name] = $closure;
			}
		}
		else
		{
			$this->_registered[$ret[] = $name] = $closure;
		}
		return $ret;
	}

	public function registered($name)
	{
		return $this->_registered && array_key_exists($name, $this->_registered);
	}

	public function exists($name)
	{
		if($this->_registered && array_key_exists($name, $this->_registered) || isset($this->$name))
		{
			return true;
		}

		$name = str_replace(['.','/'], DIRECTORY_SEPARATOR, $name);
		$filename = $this->_getPath($name.'.php');
		return stream_resolve_include_path($filename);
	}

}

class _Delayed
{
	private $closureSet;

	public function __construct($closureSet)
	{
		$this->closureSet = $closureSet;
	}

	public function __call($name, $args)
	{
		$closureSet = $this->closureSet;
		return function() use ($name, $args, $closureSet){ return $closureSet->__call($name, $args); };
	}
}
