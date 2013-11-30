<?php

namespace zf\components;

class Cookie implements \ArrayAccess
{
	private $_defaults;

	public function __construct($expire=0, $path=null, $domain=null, $secure=false, $httponly=false)
	{
		$this->_defaults = [
			'expire' => $expire,
			'path' => $path,
			'domain' => $domain,
			'secure' => $secure,
			'httponly' => $httponly,
		];
	}

	public function __get($key)
	{
		return isset($_COOKIE[$key]) ? $_COOKIE[$key] : null;
	}

	public function __set($key, $value)
	{
		$this->set($key, $value);
	}

	public function __unset($key)
	{
		$this->del($key);
	}

	public function del($key)
	{
		$this->set($key, '', ['expire'=>1]);
	}

	public function set($key, $value, $options=null)
	{
		$o = $options ? $options + $this->_defaults : $this->_defaults;
		setcookie($key, $value, $o['expire'], $o['path'], $o['domain'], $o['secure'], $o['httponly']);
	}

	public function offsetGet($offset)
	{
		return $this->__get($offset);
	}

	public function offsetExists($offset)
	{
		return isset($_COOKIE[$offset]);
	}

	public function offsetSet($offset, $value)
	{
		return $this->set($offset, $value);
	}

	public function offsetUnset($offset)
	{
		return $this->del($offset);
	}
}
