<?php

namespace zf\components;

use Exception;

class Session
{
	public function __construct($store=null)
	{
		if($store)
		{
			session_set_save_handler($store);
		}
		else
		{
			if(PHP_SESSION_DISABLED == session_status())
			{
				throw new Exception('sessions are disabled');
			}
		}
		$this->start();
		session_write_close();
	}

	public function start()
	{
		if(PHP_SESSION_ACTIVE != session_status()) session_start();
	}

	public function __get($key)
	{
		return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
	}

	public function __set($key, $value)
	{
		$this->start();
		$_SESSION[$key] = $value;
		session_write_close();
	}

	public function mset($values)
	{
		$this->start();
		foreach($values as $key => $value)
		{
			$_SESSION[$key] = $value;
		}
		session_write_close();
	}

	public function destroy()
	{
		$this->start();
		return session_destroy();
	}

	public function flash($key, $value=null)
	{
		if(is_null($value))
		{
			$ret = $this->__get($key);
			$this->__set($key, null);
			return $ret;
		}
		else
		{
			$this->__set($key, $value);
		}
	}
}
