<?php

namespace zf\components;

use Exception;
use ReflectionClass;
use Monolog\Logger;

class Monolog
{
	private $_handlerNsPrefix = '\\Monolog\\Handler\\';
	private $_levels = [
		'DEBUG'     => 100,
		'INFO'      => 200,
		'NOTICE'    => 250,
		'WARNING'   => 300,
		'ERROR'     => 400,
		'CRITICAL'  => 500,
		'ALERT'     => 550,
		'EMERGENCY' => 600,
	];
	private $_channels;

	public function __construct($channels)
	{
		$this->_channels = $channels;
	}

	public function __get($channel)
	{
		if (isset($this->_channels[$channel]))
		{
			$logger = new Logger($channel);
			foreach ($this->_channels[$channel] as $class => $config)
			{
				if (is_int($class))
				{
					$class = $config;
					$config = null;
				}
				if ($config) {
					foreach ($config as $idx => $value)
					{
						if (isset($this->_levels[$value]))
						{
							$config[$idx] = $this->_levels[$value];
							break;
						}
					}
				}
				if (strncmp('\\', $class, 1))
				{
					$class = $this->_handlerNsPrefix . $class;
				}
				$reflectionClass = new ReflectionClass($class);
				$logger->pushHandler($config
					? $reflectionClass->newInstanceArgs($config)
					: $reflectionClass->newInstance());
			}
			return $this->channel = $logger;
		}
		throw new Exception("logger '$channel' not found");
	}

	public function __call($method, $args)
	{
		return call_user_func_array([$this->default, $method], $args);
	}
}
