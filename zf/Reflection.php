<?php

namespace zf;

use Exception, InvalidArgumentException;
use ReflectionClass, ReflectionFunction;
use Closure;

class Reflection
{
	public static function apply($closure, $params=null, $context=null)
	{
		if ($context)
		{
		  	$closure = $closure->bindTo($context);
		}
		if ($params)
		{
			if (is_object($params) || Data::is_assoc($params))
			{
				$params = self::keyword2position($closure, $params);
			}
			return call_user_func_array($closure, $params);
		}
		return $closure();
	}

	public static function keyword2position($callable, $params)
	{
		if (is_array($params))
		{
			$params = (object)$params;
		}
		$ret = [];
		foreach(self::parameters($callable) as $param)
		{
			if (isset($params->{$param->name}))
			{
				$ret[] = $params->{$param->name};
			}
			elseif ($param->isOptional())
			{
				$ret[] = $param->getDefaultValue();
			}
			else
			{
				throw new exceptions\ArgumentMissingException("'$param->name' is required");
			}
		}
		return $ret;
	}

	public static function parameters($callable)
	{
		$reflection = new ReflectionFunction($callable);
		return $reflection->getParameters();
	}

	public static function getClosure($fn)
	{
		if (!$fn) throw new Exception("function name can't be empty");
		$reflection = new ReflectionFunction($fn);
		return $reflection->getClosure();
	}

	public static function parseDoc($fn)
	{
		$ret = [];
		$reflection = new ReflectionFunction($fn);
		foreach (explode("\n", $reflection->getDocComment()) as $line) {
			$line = trim($line, "* \t/");
			if($line && $line{0} === '@')
			{
				$line = substr($line, 1);
				$ret[] = strpos($line, ' ') ? explode(' ', $line, 2) : [$line, ''];
			}
		}
		return $ret;
	}

	public static function instance($className, $params, $moreParams)
	{
		$constructArgs = [];
		$reflectionClass = new ReflectionClass($className);
		$constructor = $reflectionClass->getConstructor();
		if (!$constructor)
		{
			return $reflectionClass->newInstance();
		}
		foreach($constructor->getParameters() as $param)
		{
			if(isset($params[$param->name]))
			{
				$constructArgs[] = $params[$param->name];
			}
			elseif(isset($moreParams->{$param->name}))
			{
				$constructArgs[] = $moreParams->{$param->name};
			}
			elseif($param->isOptional())
			{
				$constructArgs[] = $param->getDefaultValue();
			}
			else
			{
				throw new InvalidArgumentException("'$param->name' is required when initialize '$className'");
			}
		}
		return $reflectionClass->newInstanceArgs($constructArgs);
	}
}
