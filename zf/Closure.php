<?php

namespace zf;

use InvalidArgumentException;
use ReflectionFunction;
use ReflectionClass;

class Closure
{
	public static function apply($closure, $params=null, $context=null)
	{
		if ($context) $closure = $closure->bindTo($context);
		if ($params)
		{
			if (is_object($params) || Data::is_assoc($params))
			{
				$params = self::keywordArgs($closure, $params);
			}
			return call_user_func_array($closure, $params);
		}
		return $closure();
	}

	public static function keywordArgs($closure, $args)
	{
		if (is_array($args))
		{
			$args = (object)$args;
		}
		$reflection = new ReflectionFunction($closure);
		$ret= [];
		foreach($reflection->getParameters() as $param)
		{
			if(isset($args->{$param->name}))
			{
				$ret[] = $args->{$param->name};
			}
			else
			{
				if($param->isOptional())
				{
					$ret[] = $param->getDefaultValue();
				}
				else
				{
					throw new ArgumentMissingException("'$param->name' is required");
				}
			}
		}
		return $ret;
	}

	public static function parseDoc($fn)
	{
		$ret = [];
		$reflection = new ReflectionFunction($fn);
		foreach(explode("\n", $reflection->getDocComment()) as $line){
			$line = trim($line, "* \t/");
			if($line && $line{0} == '@')
			{
				$line = substr($line, 1);
				list($key, $line) = strpos($line, ' ') ? explode(' ', $line) : [$line, ''];
				$ret[$key][] = $line;
			}
		}
		return $ret;
	}

	public static function memorize($fn)
	{
		$results = [];
		return function() use ($results, $fn){
			$params = func_get_args();
			$key = var_export($params);
			if(!array_key_exists($key, $results)) $results[$key] = call_user_func_array($fn, $params);
			return $results;
		};
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

class ArgumentMissingException extends \Exception
{
}
