<?php

return [
	'minlen' => function($len) {
		return function($value) use ($len) { return strlen($value) >= $len; };
	},
	'maxlen' => function($len) {
		return function($value) use ($len) { return strlen($value) <= $len; };
	},
	'min' => function($min) {
		return function($value) use ($min) { return $value >= $min; };
	},
	'max' => function($max) {
		return function($value) use ($max) { return $value <= $max; };
	},
	'between' => function($min, $max) {
		return function($value) use ($min, $max) { return $value <= $max && $value >= $min; };
	},
	'in' => function($values) {
		is_array($values) or $values = func_get_args();
		return function($value) use ($values) { return in_array($value, $values, true); };
	}
];
