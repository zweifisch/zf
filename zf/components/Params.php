<?php

namespace zf\components;

class Params extends ShallowObject
{
	public function __construct($router)
	{
		$this->_source = $_GET ? array_merge($_GET, $router->params) : $router->params;
	}
}
