<?php

namespace zf\components;

use Exception;
use ArrayAccess;
use AMQPQueue;
use AMQPChannel;
use AMQPExchange;
use AMQPConnection;

class AMQP implements ArrayAccess
{
	private $_config;
	private $_queues;
	private $_exchanges;
	private $_channel;
	private $_type;

	public function __construct($host, $port, $login, $password)
	{
		$this->_config = compact('host', 'port', 'login', 'password');
	}

	public function queue($queueName)
	{
		if (empty($this->_queues[$queueName]))
		{
			$this->_queues[$queueName] = new AMQPQueue($this->channel());
			$this->_queues[$queueName]->setName($queueName);
		}
		return $this->_queues[$queueName];
	}

	public function exchange($exchangeName)
	{
		if(empty($this->_exchanges[$exchangeName]))
		{
			$this->_exchange = new AMQPExchange($this->channel());
			$this->_exchange->setName($exchangeName);
		}
		return $this->_exchange;
	}

	public function channel()
	{
		return $this->_channel
			? $this->_channel
			: $this->_channel = new AMQPChannel($this->connection());
	}

	public function connection()
	{
		if(empty($this->_connection))
		{
			$this->_connection = new AMQPConnection($this->_config);
			$this->_connection->connect();
		}
		return $this->_connection;
	}

	public function __get($key)
	{
		if ('queue' == $key || 'exchange' == $key)
		{
			$this->_type = $key;
			return $this;
		}
		else
		{
			$type = $this->getType($key);
			return $this->$type($key);
		}
	}

	public function __call($name, $args)
	{
		// $type = $this->getType();
	}

	private function getType($key)
	{
		if (!$this->_type)
		{
			throw new Exception("can't decide type of $key', queue or exchange?");
		}
		$type = $this->_type;
		$this->_type = null;
		return $type;
	}

	public function offsetGet($offset)
	{
		$type = $this->getType($offset);
		return $this->$type($offset);
	}

	public function offsetExists($offset)
	{
		$type = $this->getType($offset);
		return array_key_exists($offset, $this->{'_'.$type});
	}

	public function offsetSet($offset, $value) { }

	public function offsetUnset($offset)
	{
		$type = $this->getType($offset);
		unset($this->{'_'.$type}[$key]);
	}
}
