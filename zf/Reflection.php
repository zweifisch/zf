<?php

namespace zf;

class Reflection
{
	public function call($classname, $method, $args)
	{
		$classnameReflection = new \ReflectionClass($classname);
		$methodReflection = $classnameReflection->getMethod($method);
		if($methodReflection->isStatic())
		{
			return $methodReflection->invokeArgs(null,$args);
		}
		else if($methodReflection->isPublic())
		{
			$instance = $classnameReflection->newInstanceArgs();
			return $methodReflection->invokeArgs($instance,$args);
		}
	}

	public function getInstance($classname, $constructArgs=null)
	{
		$classnameReflection = new \ReflectionClass($classname);
		if (is_null($constructArgs))
		{
			return $classnameReflection->newInstanceArgs();
		}
		return $classnameReflection->newInstanceArgs($constructArgs);
	}

}
