<?php

namespace zf;

class Delayed
{
	public static function property($object, $property)
	{
		return function() use ($object, $property){
			return $object->$property;
		};
	}

	public static function content($path)
	{
		return function() use ($path){
			return require $path;
		};
	}

	public static function instance($className, $constructArgs)
	{
		return function() use ($className, $constructArgs) {
			$constructArgs = array_map(function($arg){
				return $arg instanceof \Closure ? $arg() : $arg;
			}, $constructArgs);
			return !Data::is_assoc($constructArgs)
				? (new ReflectionClass($className))->newInstanceArgs($constructArgs)
				: (new ReflectionClass($className))->newInstance($constructArgs);
		};
	}
}
