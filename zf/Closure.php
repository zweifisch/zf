<?php

namespace zf;

use InvalidArgumentException;
use ReflectionFunction;

class Closure
{
	public static function apply($closure, $params=null, $context=null)
	{
		if($context) $closure = $closure->bindTo($context);
		if($params)
		{
			return call_user_func_array($closure, is_assoc($params)
				? self::keywordArgs($closure, $params)
				: $params);
		}
		return $closure();
	}

	public static function keywordArgs($closure, $args)
	{
		$reflection = new ReflectionFunction($closure);
		$ret= [];
		foreach($reflection->getParameters() as $param)
		{
			if(isset($args[$param->name]))
			{
				$ret[] = $args[$param->name];
			}
			else
			{
				if($param->isOptional())
				{
					$ret[] = $param->getDefaultValue();
				}
				else
				{
					throw new InvalidArgumentException("\"$param->name\" is required");
				}
			}
		}
		return $ret;
	}

	public static function parseDoc($fn)
	{
		$ret = [];
		$key = null;
		$reflection = new ReflectionFunction($fn);
		foreach(explode("\n", $reflection->getDocComment()) as $line){
			$line = trim($line, "* \t/");
			if($line && $line{0} == '@')
			{
				$line = substr($line, 1);
				if(2 == count($exploded = explode(' ', $line)))
				{
					list($key, $line) = $exploded;
				}
				else
				{
					list($key, $line) = [$line, null];
				}
			}
			if($key && $line)
			{
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
}
