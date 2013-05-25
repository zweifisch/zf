<?php

namespace zf;

class App extends Laziness
{
	use Request;
	use Response;
	use EventEmitter;

	private $router;
	private $reflection;
	private $eagerParams = [];

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
			->set('nodebug')
			->set('charset', 'utf-8')
			->set('nopretty')
			->set('fancy');
		$this->config->load('configs.php');
		$this->router = $this->isCli() ? new CliRouter() : new Router();
		$this->helper = function(){
			return new ClosureSet($this, $this->config->helpers);
		};
		$this->requestHandlers = function(){
			return new ClosureSet($this, $this->config->handlers);
		};
		$this->paramHandlers = function(){
			return new ClosureSet($this, $this->config->params);
		};
		$this->validators = function(){
			return new ClosureSet($this, $this->config->validators);
		};
		$this->mappers = function(){
			return new ClosureSet($this, $this->config->mappers);
		};
	}

	function __call($name, $args)
	{
		if (in_array($name, ['get', 'post', 'put', 'delete', 'patch', 'head', 'cmd'], true))
		{
			$this->router->append($name, $args);
		}
		elseif ($this->helper->registered($name))
		{
			return $this->helper->__call($name, $args);
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
				throw new \Exception("signal \"$name\" not found");
			}
		}
		else
		{
			throw new \Exception("method \"$name\" not found");
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

	public function param($name, $handler, $eager=false)
	{
		$this->paramHandlers->register($name, $handler);
		if($eager)
		{
			$this->eagerParams[] = $name;
		}
		return $this;
	}

	public function helper($name, $closure=null)
	{
		$this->helper->register($name, $closure);
		return $this;
	}

	public function handler($name, $closure)
	{
		$this->requestHandlers->register($name, $closure);
		return $this;
	}

	public function validator($name, $closure)
	{
		$this->validators->register($name, $closure);
		return $this;
	}

	public function map($type, $closure)
	{
		$this->mappers->register($type, $closure);
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
		$this->requestHandlers->__call($handlerName);
	}

	public function run()
	{
		$this->requestMethod = $this->isCli() ? 'CLI' : strtoupper($_SERVER['REQUEST_METHOD']);
		list($handler, $params) = $this->router->run();
		if($handler)
		{
			if ($this->config->fancy)
			{
				$this->validators->register(require __DIR__ . DIRECTORY_SEPARATOR . 'validators.php');
				$this->mappers->register(require __DIR__ . DIRECTORY_SEPARATOR . 'mappers.php');
			}
			$this->params = new Laziness($params, $this);
			$this->processParamsHandlers($params);
			if(!$this->isCli())
			{
				$this->processParamsHandlers($_GET);
				$this->processRequestBody($this->config->fancy);
			}
			if(is_string($handler))
			{
				$this->requestHandlers->__call($handler);
			}
			else
			{
				$handler = $handler->bindTo($this);
				$handler();
			}
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

	private function processParamsHandlers($input)
	{
		foreach($input as $name => $value)
		{
			if ($this->paramHandlers->registered($name))
			{
				$args = [$value];
				$this->params->$name = in_array($name, $this->eagerParams, true)
					? $this->paramHandlers->__call($name, $args)
					: $this->paramHandlers->delayedCall($name, $args);
			}
		}
	}

}
