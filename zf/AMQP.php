<?php

namespace zf;

class AMQP
{
	private $_config;
	private $_queues;
	private $_queue;
	private $_channel;
	private $_exchanges;

	public function __construct($config)
	{
		$this->_config = $config;
	}

	public function queue($queueName)
	{
		$this->_queue = $queueName;
		return $this;
	}

	public function channel()
	{
		return $this->_channel
			? $this->_channel
			: $this->_channel = new \AMQPChannel($this->connection());
	}

	public function connection()
	{
		if(empty($this->_connection))
		{
			$this->_connection = new \AMQPConnection($this->_config);
			$this->_connection->connect();
		}
		return $this->_connection;
	}

	public function exchange($exchangeName, $type)
	{
		if(empty($this->_exchanges[$exchangeName]))
		{
			$this->_exchange = new \AMQPExchange($this->channel());
			$this->_exchange->setName($exchangeName);
			$this->_exchange->setType($type);
			$this->_exchange->declare();
		}
		return $this->_exchange;
	}

	public function declear()
	{
		if(empty($this->_queues[$this->_queue]))
		{
			$this->_queues[$this->_queue] = new \AMQPQueue($this->channel());
		}
		$this->_queues[$this->_queue]->setName($this->_queue);
		$this->_queues[$this->_queue]->declare();
		return $this;
	}

	public function bind($exchangeName, $keyname)
	{
		$this->declear();
		$this->_queues[$this->_queue]->bind($exchangeName, $keyname);
		return $this->_queues[$this->_queue];
	}

	public function __call($name, $args)
	{
		if(!substr_compare($name, 'Exchange', -8))
		{
			return $this->exchange($args[0], substr($name,0,-8));
		}
		throw new \Exception("method '$name' not defined");
	}
}
