<?php

namespace zf;

trait Closure
{
	private function getClosure($path, $closure)
	{
		$filename = $path . DIRECTORY_SEPARATOR . $closure. '.php';
		return is_readable($filename) ? require_once $filename: null;
	}

	private function callClosure($path, $closure, $context=null, $args=null)
	{
		if (is_string($closure))
		{
			$closure = $this->getClosure($path, $closure);
			if (!$closure)
			{
				throw new \Exception("closure $closure not found");
			}
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
