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
					$this->cachedConnections[$name] = new \MongoClient($config['url']);
				}
				$conenction = $this->cachedConnections[$name];
				$this->cachedClients[$name] = $conenction
					->selectDB($config['database'])
					->selectCollection($config['collection']);
			}
		}
		return $this->cachedClients[$name];
	}
}
