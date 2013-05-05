<?php

namespace zf;

class Mongo
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
		if (empty($this->cachedClients[$name]))
		{
			if(isset($this->config[$name]))
			{
				$config = $this->config[$name];
				if(empty($this->cachedConnections[$config['url']]))
				{
					$options = isset($config['options']) ? $config['options'] : [];
					$this->cachedConnections[$name] = new \MongoClient($config['url'], $options);
				}
				$ret = $this->cachedConnections[$name];
				if (isset($config['database'])) $ret = $ret->selectDB($config['database']);
				if (isset($config['collection'])) $ret = $ret->selectCollection($config['collection']);
				$this->cachedClients[$name] = $ret;
			}
		}
		return $this->cachedClients[$name];
	}
}
