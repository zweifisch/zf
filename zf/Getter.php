<?php

namespace zf;

use Exception;

trait Getter
{
	public function __get($key)
	{
		$getter = 'get' . ucfirst($key);
		if (method_exists($this, $getter))
		{
			return $this->$getter();
		}
		if (method_exists($this, 'propertyMissing'))
		{
			return $this->propertyMissing($key);
		}
		throw new Exception("can't get '$key', '$getter' not defined on ".__CLASS__);
	}

	public function __isset($key)
	{
		return method_exists($this, 'get' . ucfirst($key));
	}
}
