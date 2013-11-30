<?php

namespace zf;

class Data
{
	static function flatten(array $array)
	{
		$ret = [];
		array_walk_recursive($array, function($item) use (&$ret){
			$ret[] = $item;
		});
		return $ret;
	}

	static function is_assoc(array $array)
	{
		return (bool)count(array_filter(array_keys($array), 'is_string'));
	}

	static function pushLeft(array $array)
	{
		$ret = [];
		$currentKey = null;
		foreach($array as $key => $value)
		{
			if (is_int($key))
			{
				$ret[$currentKey][] = $value;
			}
			else
			{
				$currentKey = $key;
				$ret[$key] = [$value];
			}
		}
		return $ret;
	}

	static function pushRight(array $array)
	{
		$ret = [];
		$items = [];
		foreach($array as $key => $value)
		{
			if (is_int($key))
			{
				$items[] = $value;
			}
			else
			{
				while($items) $ret[array_shift($items)] = $value;
				$ret[$key] = $value;
			}
		}
		return $ret;
	}

}
