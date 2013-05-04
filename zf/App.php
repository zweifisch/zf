<?php

namespace zf;

class App extends Laziness
{
	use Request;
	use Response;

	private $paramHandlers = [];
	private $router;
	private $reflection;
	public $name = 'App';

	function __construct()
	{
		parent::__construct();
		$this->register('config','\\'.__NAMESPACE__.'\\Config');
		$this->router = $this->isCli() ? new CliRouter() : new Router();
		$this->requestMethod = $this->isCli() ? 'CLI' : strtoupper($_SERVER['REQUEST_METHOD']);

		$this->reflection = new Reflection();

		$this->config('handlers', 'handlers');
		$this->config('helpers', 'helpers');
		$this->config('params', 'params');
		$this->config('views', 'views');
		$this->config('viewext', '.php');
		$this->config('pretty', false);
		$this->config('rootdir', $this->isCli() ? dirname(realpath($_SERVER['argv'][0])) : $_SERVER['DOCUMENT_ROOT']);
	}

	function __call($name, $args)
	{
		if (in_array($name, ['get', 'post', 'put', 'delete', 'patch', 'cmd'], true))
		{
			$this->router->append($name, $args);
		}
		elseif ($this->isCli())
		{
			if (0 == strncmp('sig', $name, 3))
			{
				$name = strtoupper($name);
				if (defined($name))
				{
					pcntl_signal(constant($name), $args[0]->bindTo($this));
				}
				else
				{
					throw new \Exception("signal $name not found");
				}
			}
		}
		else
		{
			throw new \Exception("method $name not found");
		}
		return $this;
	}

	public function config($name,$value)
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
			$this->params = new Laziness($params, $this);
			$this->processParams();
			$this->isCli() or $this->processRequestParams();
			$this->call('handlers', $callable);
		}
		else
		{
			$this->notFound();
		}
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
				$handler = $this->paramHandlers[$name];
				$args = [$value];
				$this->params->$name = function() use ($handler, $args){
					return $this->call('params', $handler, $args);
				};
			}
		}
	}

}
