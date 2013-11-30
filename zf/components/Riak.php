<?php

namespace zf\components;

class Riak implements \ArrayAccess
{
	private $_client;
	private $_bucket;
	private $_config;
	public function __construct($host, $port)
	{
		$this->_config = [
			'host'=> $host,
			'port' => $port,
		];
	}

	public function __get($key)
	{
		$this->_client or $this->_client = new \Basho\Riak\Riak($this->_config['host'], $this->_config['port']);
		if ($this->_bucket)
		{
			$ret = $this->_bucket->get($key);
			$this->_bucket = null;
			return $ret;
		}
		else
		{
			$this->_bucket = $this->_client->bucket($key);
			return $this;
		}
	}

	public function __set($key, $value)
	{
		if (!$this->_bucket)
		{
			throw new \Exception('no bucket specified');
		}
		$this->_bucket->newObject($key, $value)->store();
		$this->_bucket = null;
	}

	public function __call($method, $args)
	{
		if ($this->_bucket)
		{
			return call_user_func_array([$this->_bucket, $method], $args);
		}
		else
		{
			return call_user_func_array([$this->_client, $method], $args);
		}
	}

	public function offsetGet($offset)
	{
		return $this->_bucket ? $this->__get($offset)->getData() : $this->__get($offset);
	}

	public function offsetExists($offset)
	{
		if (!$this->_bucket)
		{
			throw new \Exception('no bucket specified');
		}
		return $this->__get($offset)->exists();
	}

	public function offsetSet($offset, $value)
	{
		$this->__set($offset, $value);
	}

	public function offsetUnset($offset)
	{
		if (!$this->_bucket)
		{
			throw new \Exception('no bucket specified');
		}
		return $this->__get($offset)->delete();
	}
}
