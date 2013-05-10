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
	private $router;
	private $reflection;

	function __construct()
	{
		parent::__construct();
		$this->reflection = new Reflection();
		$this->register('config','\\'.__NAMESPACE__.'\\Config');
		$this->config('handlers', 'handlers')
			->config('helpers', 'helpers')
			->config('params', 'params')
			->config('views', 'views')
			->config('viewext', '.php')
			->config('nopretty')
			->config('fancy');
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

	public function config($name,$value=null,$overwrite=true)
	{
		if(1 == func_num_args())
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
				if(0 == strncmp('no', $name, 2))
				{
					$name = substr($name, 2);
					$this->config->$name = false;
				}
				else
				{
					$this->config->$name = true;
				}
			}
		}
		else
		{
			if ($overwrite || !isset($this->config->$name))
			{
				$this->config->$name = $value;
			}
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
