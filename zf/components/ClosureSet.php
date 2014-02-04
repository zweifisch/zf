<?php

namespace zf\components;

use Exception, Closure;
use zf\Reflection;

class ClosureSet
{
	private $_path;
	private $_namespacePrefix;
	private $_context;

	private $_namespace;

	private $_registered = [];

	public $delayed;

	public function __construct($path, $namespace, $context=null, $closures=null)
	{
		$this->_path = str_replace('/', DIRECTORY_SEPARATOR, $path);
		$this->_namespacePrefix = $namespace;
		$this->_context = $context;
		$this->delayed = new _Delayed($this);
		if($closures) $this->register($closures);
	}

	public function resolve($fn)
	{
		$fn = str_replace('.', '/', $fn);
		$fullname = '\\' . $this->_namespacePrefix . '\\' . str_replace('/', '\\', $fn);
		if (!function_exists($fullname)) {
			$segments = explode('/', $fn);
			array_pop($segments);
			$baseFilename = $segments
				? $this->_path .DIRECTORY_SEPARATOR. implode(DIRECTORY_SEPARATOR, $segments)
				: $this->_path;
			$filename = $baseFilename . '.php';
			if (!stream_resolve_include_path($filename))
			{
				$filename = $baseFilename . DIRECTORY_SEPARATOR . 'index.php';
			}
			if (!stream_resolve_include_path($filename))
			{
				return false;
			}
			require $filename;
		}
		return $fullname;
	}

	public function __call($name, $params)
	{
		if(isset($this->_registered[$name]))
		{
			$closure = $this->_registered[$name];
		}
		else
		{
			$this->_namespace[] = $name;
			$fn = implode('/', $this->_namespace);
			$this->_namespace = null;
			if (!$fullname = $this->resolve($fn))
			{
				throw new Exception("can't locate $fn in $this->_path");
			}
			$closure = Reflection::getClosure($fullname);
		}
		if ($this->_context)
		{
			$closure = $closure->bindTo($this->_context);
		}
		return call_user_func_array($closure, $params);
	}

	public function __get($key)
	{
		$this->_namespace[] = $key;
		return $this;
	}

	public function register($name, $closure=null)
	{
		if ($closure)
		{
			$this->_registered[$name] = $closure;
		}
		else
		{
			$this->_registered = array_merge($this->_registered, $name);
		}
		return $this;
	}

	public function registered($name)
	{
		return array_key_exists($name, $this->_registered);
	}

	public function exists($name)
	{
		return $this->registered($name) || $this->resolve($name);
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
		return function() use ($name, $args, $closureSet) {
			return $closureSet->__call($name, $args);
		};
	}
}
