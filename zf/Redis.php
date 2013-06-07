<?php

namespace zf;

class Redis
{

	private $_config;
	private $_cachedConnections = [];

	public function __construct($config)
	{
		$this->_config = $config;
	}

	public function __get($name)
	{
		if(empty($this->_config[$name]))
			throw new \Exception("redis \"$name\" not defined");

		$config = $this->_config[$name];
		$host = isset($config['host']) ? $config['host'] : '127.0.0.1';
		$port = isset($config['port']) ? $config['port'] : 6379;
		$timeout = isset($config['timeout']) ? $config['timeout'] : 0;
		$pconnect = isset($config['pconnect']) ? $config['pconnect'] : false;

		if(empty($this->_cachedConnections["$host:$port"]))
		{
			$redis = new \Redis();
			$pconnect
				? $redis->pconnect($host, $port, $timeout)
				: $redis->connect($host, $port, $timeout);
			$this->_cachedConnections["$host:$port"] = $redis;
		}
		return $this->$name = $this->_cachedConnections["$host:$port"];
	}
}
