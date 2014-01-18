<?php

namespace zf\lazy;

use Exception;

trait Getter
{
	public function __get($key)
	{
		$getter = 'get' . ucfirst($key);
		if (method_exists($this, $getter))
		{
			return $this->$key = $this->$getter();
		}
		throw new Exception("can't get '$key', '$getter' not defined on ".__CLASS__);
	}

	public function __isset($key)
	{
		return method_exists($this, 'get' . ucfirst($key));
	}
}
