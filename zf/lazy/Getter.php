<?php

namespace zf\lazy;

use Exception;

trait Getter
{
	private $_caches;
	public function __get($key)
	{
		if (!isset($this->_caches[$key]))
		{
			$getter = 'get' . ucfirst($key);
			if (method_exists($this, $getter))
			{
				$this->_caches[$key] = $this->$getter();
			}
			else
			{
				throw new Exception("can't get '$key', '$getter' not defined on ".__CLASS__);
			}
		}
		return $this->_caches[$key];
	}

	public function __isset($key)
	{
		return method_exists($this, 'get' . ucfirst($key));
	}
}
