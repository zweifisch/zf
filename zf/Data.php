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
}
