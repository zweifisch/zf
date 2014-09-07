<?php

namespace zf\components;

class Query extends ShallowObject
{

	public function __construct()
	{
        $this->_source = $_GET;
	}
}
