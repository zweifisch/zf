<?php

return [
	'Int'=> function($value){ return (int)$value; },
	'Num'=> function($value){ return (float)$value; },
	'Str'=> function($value){ return is_array($value) ? '' : (string)$value; },
	'Array'=> function($value){ return (array)$value; },
];
