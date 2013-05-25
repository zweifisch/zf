<?php

namespace zf;

class Closure
{
	private $cachedClosures;
	private $lookupPath;
	private $context;
	private static $instances;

	public function __construct($context=null, $lookupPath=null)
	{
		$this->context = $context;
		$this->lookupPath = $lookupPath;
	}

	public function getInstance($context, $lookupPath)
	{
		isset(self::$instances[$lookupPath]) or self::$instances[$lookupPath] = new self($context,$lookupPath);
		return self::$instances[$lookupPath];
	}

	public function get($closureName, $useCache=true)
	{
		$key = $this->lookupPath.DIRECTORY_SEPARATOR.$closureName;
		if ($useCache && isset($cachedClosures[$key]))
		{
			return $cachedClosures[$key];
		}

		$filename = $key.'.php';
		$closure = is_readable($filename) ? require $filename: null;

		if (!$closure)
		{
			throw new \Exception("closure \"$closureName\" not found under \"$this->lookupPath\"");
		}
		elseif (1 === $closure)
		{
			throw new \Exception("invalid closure in \"$this->lookupPath/$closureName.php\", forgot to return the closure?");
		}

		if ($useCache)
		{
			$cachedClosures[$key] = $closure;
		}
		return $closure;
	}

	public function call($closure, $args=null)
	{
		if (!$closure instanceof \Closure)
		{
			throw new \Exception("invalid closure in $path $closure");
		}
		is_null($this->context) or $closure = $closure->bindTo($this->context);
		if (!$args) return $closure();
		$numArgs = count($args);
		return
			(1 == $numArgs ? $closure($args[0]) :
			(2 == $numArgs ? $closure($args[0], $args[1]) :
			(3 == $numArgs ? $closure($args[0], $args[1], $args[2]) : call_user_func_array($closure, $args))));
	}

	public static function callWithContext($closure, $context, $path, $args=null)
	{
		$instance = self::getInstance($context, $path);
		if(is_string($closure))
		{
			$closure = $instance->get($closure);
		}
		return $instance->call($closure, $args);
	}
}
