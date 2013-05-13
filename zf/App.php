<?php

namespace zf;

class App extends Laziness
{
	use Request;
	use Response;
	use EventEmitter;
	use Closure;

	private $paramHandlers;
	private $requestHandlers;
	private $validators;
	private $mappers;
	private $router;
	private $reflection;

	function __construct()
	{
		parent::__construct();
		$this->reflection = new Reflection();
		$this->register('config','\\'.__NAMESPACE__.'\\Config');
		$this->set('handlers', 'handlers')
			->set('helpers', 'helpers')
			->set('params', 'params')
			->set('views', 'views')
			->set('validators', 'validators')
			->set('mappers', 'mappers')
			->set('viewext', '.php')
			->set('nopretty')
			->set('fancy');
		$this->config->load('configs.php');
		$this->router = $this->isCli() ? new CliRouter() : new Router();
		$this->register('helper', '\\'.__NAMESPACE__.'\\Helper', $this, $this->config->helpers);
	}

	function __call($name, $args)
	{
		if (in_array($name, ['get', 'post', 'put', 'delete', 'patch', 'cmd'], true))
		{
			$this->router->append($name, $args);
		}
		elseif ($this->isCli() && (0 == strncmp('sig', $name, 3)))
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
		else
		{
			throw new \Exception("method $name not found");
		}
		return $this;
	}

	public function set($name, $value=null)
	{
		if(1 == func_num_args())
		{
			$this->config->set($name);
		}
		else
		{
			$this->config->set($name, $value);
		}
		return $this;
	}

	public function param($name, $callable, $eager=false)
	{
		$this->paramHandlers[$name] = [$callable,$eager];
		return $this;
	}

	public function helper($name, $closure)
	{
		$this->helper->register($name, $closure);
		return $this;
	}

	public function handler($name, $closure)
	{
		$this->requestHandlers[$name] = $closure;
		return $this;
	}

	public function validator($name, $closure)
	{
		$this->validators[$name] = $closure;
		return $this;
	}

	public function map($type, $closure)
	{
		$this->mappers[$type] = $closure;
		return $this;
	}

	public function register($alias, $className)
	{
		$constructArgs = array_slice(func_get_args(), 2);
		$this->$alias = function() use ($className, $constructArgs){
			return $this->reflection->getInstance($className, $constructArgs);
		};
		return $this;
	}

	public function pass($handlerName)
	{
		$handler = isset($this->requestHandlers[$handlerName])? $this->requestHandlers[$handlerName] : $handlerName;
		$this->callClosure('handlers', $handler, $this);
	}

	public function run()
	{
		$this->requestMethod = $this->isCli() ? 'CLI' : strtoupper($_SERVER['REQUEST_METHOD']);
		list($callable, $params) = $this->router->run();
		if($callable)
		{
			$this->params = new Laziness($params, $this);
			$this->processParams();
			if ($this->config->fancy)
			{
				$validators = require __DIR__ . DIRECTORY_SEPARATOR . 'validators.php';
				$this->validators = is_array($this->validators)
					? array_merge($validators, $this->validators)
					: $validators;
				$mappers = require __DIR__ . DIRECTORY_SEPARATOR . 'mappers.php';
				$this->mappers = is_array($this->mappers)
					? array_merge($mappers, $this->mappers)
					: $mappers;
			}
			$this->isCli() or $this->processRequestParams($this->config->fancy);
			$this->callClosure('handlers', $callable, $this);
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

	private function processParams()
	{
		foreach($this->params->getAll() as $name => $value)
		{
			if (isset($this->paramHandlers[$name]))
			{
				list($handler,$eager) = $this->paramHandlers[$name];
				$args = [$value];
				if ($eager)
				{
					$this->params->$name = $this->callClosure('params', $handler, $this, $args);
				}
				else
				{
					$this->params->$name = function() use ($handler, $args){
						return $this->callClosure('params', $handler, $this, $args);
					};
				}
			}
		}
	}

}
