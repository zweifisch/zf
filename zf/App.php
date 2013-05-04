<?php

namespace zf;

class App extends Laziness
{
	use Request;
	use Response;

	private $paramHandlers = [];
	private $nsPrefix = '\\';
	private $router;
	private $components;
	private $reflection;

	function __construct()
	{
		$this->register('config','\\'.__NAMESPACE__.'\\Config');
		$this->router = $this->isCli() ? new CliRouter() : new Router();
		$this->requestMethod = $this->isCli() ? 'CLI' : strtoupper($_SERVER['REQUEST_METHOD']);

		$this->reflection = new Reflection();

		$this->config('handlers', 'handlers');
		$this->config('helpers', 'helpers');
		$this->config('params', 'params');
		$this->config('views', 'views');
		$this->config('viewext', '.php');
		$this->config('rootdir', $this->isCli() ? dirname(realpath($_SERVER['argv'][0])) : $_SERVER['DOCUMENT_ROOT']);
	}

	function __call($name, $args)
	{
		if (in_array($name, ['get', 'post', 'put', 'delete', 'patch', 'cmd'], true))
		{
			list($pattern, $callable) = $args;
			if(is_array($callable))
			{
				list($classname, $method) = $callable;
				$callable = [$this->nsPrefix . '\\' . $classname, $method];
			}
			$this->router->append($name, [$pattern, $callable]);
			return $this;
		}
		elseif ($this->isCli())
		{
			if (0 == strncmp('sig', $name, 3))
			{
				$name = strtoupper($name);
				if (defined($name))
				{
					list($handler) = $args;
					pcntl_signal(constant($name), $handler->bindTo($this));
				}
				else
				{
					throw new \Exception("method $name not found");
				}
			}
		}
		else
		{
			throw new \Exception("method $name not found");
		}
	}

	function config($name,$value)
	{
		if(is_array($name))
		{
			foreach($name as $key=>$value)
			{
				$this->config->$key = $value;
			}
		}
		else
		{
			$this->config->$name = $value;
		}
	}

	public function register($alias,$className,$constructArg=null)
	{
		$this->$alias = function() use ($className, $constructArg){
			return is_null($constructArg)
				? $this->reflection->getInstance($className)
				: $this->reflection->getInstance($className, [$constructArg]);
		};
		return $this;
	}

	public function run()
	{
		list($callable, $params) = $this->router->run();
		if($callable)
		{
			$this->params = new Laziness($params);
			$this->processParams();
			$this->isCli() or $this->processRequestParams();
			if (is_array($callable))
			{
				list($classname, $method) = $callable;
				$this->reflection->call($classname, $method, [$this->params, $this]);
			}
			else
			{
				$this->call('handlers', $callable);
			}
		}
		else
		{
			$this->notFound();
		}
	}

	public function ns($nsPrefix)
	{
		$this->nsPrefix = $nsPrefix;
		return $this;
	}

	public function defaults($defaults)
	{
		$this->router->attach($defaults);
		return $this;
	}

	private function call($type, $closure, $args = null)
	{
		if (is_string($closure))
		{
			$path = $this->config->$type . DIRECTORY_SEPARATOR . $closure. '.php';
			if (!is_readable($path)) throw new \Exception("$closure($path) not found");
			$closure = require_once $path;
		}
		$closure = $closure->bindTo($this);
		return $args ? call_user_func_array($closure, $args) : $closure();
	}

	private function processParams()
	{
		foreach($this->params->getAll() as $name => $value)
		{
			if (isset($this->paramHandlers[$name]))
			{
				$this->params->$name = $this->call('params', $this->paramHandlers[$name], [$value]);
			}
		}
	}

}
