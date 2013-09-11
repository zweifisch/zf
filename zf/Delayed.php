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
}
