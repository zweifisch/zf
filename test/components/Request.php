<?php

namespace components;

class Request extends \zf\components\Request
{
	public function getMethod()
	{
		return 'GET';
	}

    public function getPath()
    {
        return '/';
    }
}
