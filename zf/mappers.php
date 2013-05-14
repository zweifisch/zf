<?php

namespace zf;

return [
	'Int'=> function($value){ return (int)$value; },
	'Num'=> function($value){ return (float)$value; },
	'Str'=> function($value){ return is_array($value) ? '' : (string)$value; },
	'Array'=> function($value){ return (array)$value; },
	'File'=> function($file, $path){
		if(empty($_FILES[$path])) return null;
		if(empty($file['tmp_name'])) return null;
		$file['extension'] = pathinfo($file['name'], PATHINFO_EXTENSION);
		$file['content'] = function() use ($file){
			return file_get_contents($file['tmp_name']);
		};
		return new Laziness($file, $this);
	},
];
