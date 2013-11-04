<?php

namespace zf\components;

class Riak implements \ArrayAccess
{
	private $_client;
	private $_bucket;
	private $_config;
	public function __construct($config)
	{
		$this->_config = $config;
	}

	public function __get($key)
	{
		$this->_client or $this->_client = new \Basho\Riak\Riak($this->_config['ip'], $this->_config['port']);
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
	}

	public function offsetGet($offset)
	{
		return $this->__get($offset);
	}

	public function offsetExists($offset) { }

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
		$this->__get(offsetSet)->delete();
	}
}
