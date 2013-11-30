<?php

namespace zf\components;

use Exception;

class Redis
{
	private $_dbs;
	private $_cachedConnections = [];
	private $_key;
	private $_connection;

	public function __construct($dbs)
	{
		$this->_dbs = $dbs;
	}

	public function __get($key)
	{
		if ($this->_connection)
		{
			$this->_key = $key;
		}
		else
		{
			if (empty($this->_dbs[$key]))
			{
				$this->_key = $key;
			}
			else
			{
				$this->getConnection($key);
			}
		}
		return $this;
	}

	private function useDefaultConnection()
	{
		if (isset($this->_dbs['default']))
		{
			$this->getConnection('default');
		}
		else
		{
			throw new \Exception("redis db \"$key\" not defined");
		}
	}

	private function getConnection($key)
	{
		if(!array_key_exists($key, $this->_cachedConnections))
		{
			$config = $this->_dbs[$key] + [
				'host' => '127.0.0.1',
				'port' => 6379,
				'index' => 0,
				'prefix' => '',
				'timeout' => 0,
				'pconnect' => false,
			];
			$this->_cachedConnections[$key] = $redis = new \Redis();
			$config['pconnect']
				? $redis->pconnect($config['host'], $config['port'], $config['timeout'])
				: $redis->connect($config['host'], $config['port'], $config['timeout']);
			$config['index'] && $redis->select($config['index']);
			$config['prefix'] && $redis->setOption(\Redis::OPT_PREFIX, $config['prefix']);
		}
		return $this->_connection = $this->_cachedConnections[$key];
	}

	public function __call($method, $args)
	{
		$this->_connection or $this->useDefaultConnection();
		if ($this->_key)
		{
			array_unshift($args, $this->_key);
			$this->_key = null;
		}
		$ret = call_user_func_array([$this->_connection, $method], $args);
		$this->_connection = null;
		return $ret;
	}

	public function __set($key, $value)
	{
		$this->_connection or $this->useDefaultConnection();
		if ($this->_key)
		{
			$this->_connection->hset($this->_key, $key, $value);
			$this->_key = null;
		}
		else
		{
			$this->_connection->set($key, $value);
		}
	}
}
