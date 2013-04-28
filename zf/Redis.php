<?php

namespace zf;

class Redis
{

	private $config;
	private $cachedConnections;
	private $cachedClients;

	public function __construct($config)
	{
		$this->cachedConnections = array();
		$this->cachedClients = array();
		$this->config = $config;
	}

	public function __get($name)
	{
		if(empty($this->cachedClients[$name]))
		{
			if(isset($this->config[$name]))
			{
				$config = $this->config[$name];
				$host = isset($config['host']) ? $config['host'] : '127.0.0.1';
				$port = isset($config['port']) ? $config['port'] : 6379;
				$timeout = isset($config['timeout']) ? $config['timeout'] : 5;
				$pconnect = isset($config['pconnect']) ? $config['pconnect'] : false;

				if (empty($this->cachedConnections["$host:$port"]))
				{
					$redis = new \Redis();
					if ($pconnect)
					{
						$redis->pconnect($host, $port, $timeout);
					}
					else
					{
						$redis->connect($host, $port, $timeout);
					}
					$this->cachedConnections["$host:$port"] = $redis;
				}
				$conenction = $this->cachedConnections["$host:$port"];
				$this->cachedClients[$name] = $conenction;
			}
		}
		return $this->cachedClients[$name];
	}
}
