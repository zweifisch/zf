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
}
