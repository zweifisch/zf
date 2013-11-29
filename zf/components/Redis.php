<?php

namespace zf\components;

class Redis
{

	private $_config;
	private $_cachedConnections = [];
	private $_keys;
	private $_key;
	private $_type;
	private $_connection;

	public function __construct($config)
	{
		$this->_config = $config;
	}

	public function __get($name)
	{
		if($this->_key)
		{
			if('hash' !== $this->_type) throw new \Exception("$this->_key is not declared as a hash");

			$key = $this->_key;
			$this->_key = null;
			return $this->_connection->hget($key, $name);
		}

		if(empty($this->_config[$name]))
			throw new \Exception("redis config \"$name\" not defined");

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

	private function _parseConfig()
	{
		foreach($this->_config as $alias=>$conf)
		{
			if(empty($conf['keys'])) continue;

			foreach($conf['keys'] as $key=>$pattern)
			{
				if(is_int($key))
				{
					if(empty($lastkey)) throw new \Exception("unexpected \"$pattern\" in redis config \"$alias\"");
					$this->_keys[$lastkey.':t'] = $pattern;
				}
				else
				{
					$this->_keys[$lastkey = $key] = $pattern;
					$this->_keys[$key.':c'] = $alias;
				}
			}
		}
	}

	public function __call($name, $args)
	{
		$this->_keys or $this->_parseConfig();

		if($this->_key)
		{
			array_unshift($args, $this->_key);
			$this->_key = null;
			return call_user_func_array([$this->_connection, $name], $args);
		}

		if(empty($this->_keys[$name])) throw new \Exception("key \"$name\" not defined");

		$this->_connection = $this->{$this->_keys[$name.':c']};
		$this->_type = isset($this->_keys[$name.':t']) ? isset($this->_keys[$name.':t']) : null; 
		$this->_key = vsprintf($this->_keys[$name], $args);
		return $this;
	}

	public function __set($name, $value)
	{
		if($this->_key)
		{
			if('hash' !== $this->_type) throw new \Exception("$this->_key is not declared as a hash");

			$key = $this->_key;
			$this->_key = null;
			return $this->_connection->hset($key, $name, $value);
		}

		if(isset($this->_config[$name]))
		{
			return $this->$name = $value;
		}

		throw new \Exception("can't set '$name'");
	}
}
