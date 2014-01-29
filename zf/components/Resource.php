<?php

namespace zf\components;

class Resource
{
	private $_path;
	private $_namespace;

	public function __construct($path, $namespace)
	{
		$this->_path = str_replace('/', DIRECTORY_SEPARATOR, $path);
		$this->_namespace = $namespace;
	}

	public function resolve($fn)
	{
		$fullname = '\\' . $this->_namespace . '\\' . str_replace('/', '\\', $fn);

		if (!function_exists($fullname)) {
			$segments = explode('/', $fn);
			array_pop($segments);
			require $this->_path .DIRECTORY_SEPARATOR. implode(DIRECTORY_SEPARATOR, $segments). '.php';
		}
		return $fullname;
	}
}
