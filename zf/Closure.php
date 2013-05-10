<?php

namespace zf;

trait Closure
{
	private $cachedClosures;

	private function getClosure($path, $closureName, $useCache=true)
	{
		$key = $path.DIRECTORY_SEPARATOR.$closureName;
		if ($useCache && isset($cachedClosures[$key]))
		{
			return $cachedClosures[$key];
		}

		$filename = $key.'.php';
		$closure = is_readable($filename) ? require $filename: null;

		if (!$closure)
		{
			throw new \Exception("closure $closureName not found");
		}
		elseif (1 === $closure)
		{
			throw new \Exception("invalid closure in $path/$closureName.php, forgot to return the closure?");
		}

		if ($useCache)
		{
			$cachedClosures[$key] = $closure;
		}
		return $closure;
	}

	private function callClosure($path, $closure, $context=null, $args=null)
	{
		if (is_string($closure))
		{
			$closureName = $closure;
			$closure = $this->getClosure($path, $closureName);
		}
		if (!$closure instanceof \Closure)
		{
			throw new \Exception("invalid closure in $path $closure");
		}
		is_null($context) or $closure = $closure->bindTo($context);
		if (!$args) return $closure();
		$numArgs = count($args);
		return
			(1 == $numArgs ? $closure($args[0]) :
			(2 == $numArgs ? $closure($args[0], $args[1]) :
			(3 == $numArgs ? $closure($args[0], $args[1], $args[2]) : call_user_func_array($closure, $args))));
	}
}
