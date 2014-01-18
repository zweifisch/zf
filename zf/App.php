<?php

namespace zf;

use Exception;
use FilesystemIterator;

class App extends Laziness
{
	use EventEmitter;
	use helpers\Restful;
	use helpers\JsonRpc;

	private $_lastComponent;
	private $_middlewares;

	public $config;

	function __construct()
	{
		ob_start();
		ob_implicit_flush(false);
		parent::__construct();

		defined('IS_CLI') or define('IS_CLI', 'cli' == PHP_SAPI);

		$basedir = IS_CLI ? dirname(realpath($_SERVER['argv'][0])) : $_SERVER['DOCUMENT_ROOT'];
		set_include_path($basedir . PATH_SEPARATOR . get_include_path());

		$this->config = new Config($this);
		$this->config->load(__DIR__ . DIRECTORY_SEPARATOR . 'defaults.php');
		$this->config->load('configs.php', true);
		getenv('ZF_ENV') && $this->config->load('configs-'.getenv('ZF_ENV').'.php', true);
		$this->config->basedir = $basedir;

		$on_exception = function($exception) {
			if(!$this->emit('exception', $exception)) throw $exception;
		};
		set_exception_handler($on_exception->bindTo($this));

		$on_shutdown = function(){
			$this->emit('shutdown');
		};
		register_shutdown_function($on_shutdown->bindTo($this));

		$on_error = function() {
			return $this->emit('error', (object)array_combine(
				['no','str','file','line','context'], func_get_args()));
		};
		set_error_handler($on_error->bindTo($this));

		$this->useMiddleware($this->config->use);
	}

	function __call($name, $args)
	{
		if(in_array($name, ['post', 'put', 'delete', 'patch', 'head', 'cmd'], true))
		{
			$pattern = array_shift($args);
			$this->router->append($name, $pattern, $args);
			return $this;
		}

		if($this->helper->registered($name))
		{
			return $this->helper->__call($name, $args);
		}

		foreach(['on', 'emit', 'sig'] as $prefix)
		{
			if(!strncmp($prefix, $name, strlen($prefix)))
			{
				return $this->{'_' . $prefix}(substr($name, strlen($prefix)), $args);
			}
		}

		throw new Exception("method '$name' not found");
	}

	private function _sig($signal, $args)
	{
		$signal = 'SIG' . strtoupper($signal);
		if(!defined($signal))
		{
			throw new Exception("signal '$signal' not found");
		}
		return pcntl_signal(constant($signal), $args[0]->bindTo($this));
	}

	private function _on($event, $args)
	{
		$event = strtolower($event);
		if(!isset($this->config->events[$event]))
		{
			throw new Exception("event '$event' not defined");
		}
		return $this->on($event, $args[0]);
	}

	private function _emit($event, $args)
	{
		$event = strtolower($event);
		if(!isset($this->config->events[$event]))
		{
			throw new Exception("event '$event' not defined");
		}
		return $this->emit($event, $args[0]);
	}

	public function __get($key)
	{
		if(!parent::__isset($key) && isset($this->config->components) && isset($this->config->components[$key]))
		{
			$this->register($key); 
		}
		return parent::__get($key);
	}

	public function __isset($key)
	{
		return parent::__isset($key) || isset($this->config->components[$key]);
	}

	public function module($name)
	{
		return $this;
	}

	public function useModule()
	{
		$modules = func_get_args();
		foreach($modules as $module)
		{
			$this->router->module($module);
			require 'modules'.DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR.'index.php';
		}
	}

	public function set($name, $value=null)
	{
		1 == func_num_args()
			? $this->config->set($name)
			: $this->config->set($name, $value);
		return $this;
	}

	public function get($key)
	{
		if(1 == func_num_args())
		{
			if (ucfirst($key) == $key)
			{
				return isset($_SERVER[$key]) ? $_SERVER[$key] : null;
			}
			else
			{
				return isset($this->config->$key) ? $this->config->$key : null;
			}
		}
		else
		{
			$args = func_get_args();
			$pattern = array_shift($args);
			$this->router->append('GET', $pattern, $args);
			return $this;
		}
	}

	public function param($name, $handler=null)
	{
		$this->paramHandlers->register($name, $handler);
		return $this;
	}

	public function helper($name, $closure=null)
	{
		$this->helper->register($name, $closure);
		return $this;
	}

	public function handler($name, $closure)
	{
		$this->handlers->register($name, $closure);
		return $this;
	}

	public function middleware($name, $closure)
	{
		$this->middlewares->register($name, $closure);
		return $this;
	}

	public function useMiddleware($middlewares)
	{
		if (!is_array($middlewares))
		{
			$middlewares = func_get_args();
			if (2 == count($middlewares) && $middlewares[1] instanceof \Closure) {
				$this->middlewares->register($middlewares[0], $middlewares[1]);
				$this->_middlewares[] = [$middlewares[0], []];
				return $this;
			}
		}

		$this->_middlewares = $this->_middlewares
			? array_merge($this->_middlewares, $this->prepareMiddlewares($middlewares))
			: $this->prepareMiddlewares($middlewares);
		return $this;
	}

	public function register($name, $className=null, $constructArgs=null)
	{
		$this->_lastComponent = $name;
		if($className && $className instanceof \Closure)
		{
			$this->$name = $className;
		}
		else
		{
			$this->$name = function() use ($className, $constructArgs, $name) {
				$constructArgs or $constructArgs = [];
				if(isset($this->config->components) && isset($this->config->components[$name]))
				{
					$defaultArgs = $this->config->components[$name]['constructArgs'];
					$constructArgs = $constructArgs ? array_merge($defaultArgs, $constructArgs) : $defaultArgs;
					$className = $this->config->components[$name]['class'];
				}

				$constructArgs = array_map(function($arg) {
					return $arg instanceof \Closure ? $arg() : $arg;
				}, $constructArgs);

				return Closure::instance($className, $constructArgs, $this);
			};
		}
		return $this;
	}

	public function initialized($componentName, $callback=null)
	{
		if (!$callback) {
			list($componentName, $callback) = [$this->_lastComponent, $componentName];
		} 
		$this->on('computed', function($data) use ($componentName, $callback) {
			if($data['key'] == $componentName)
			{
				$callback = $callback->bindTo($this);
				$callback($data['value']);
				return true;
			}
		});
	}

	public function pass($handlerName)
	{
		return $this->handlers->__call($handlerName);
	}

	public function render($viewName, $vars=null)
	{
		return $this->response->render($viewName, $vars);
	}

	public function run()
	{
		list($handlers, $module) = $this->request->route();

		if($handlers)
		{
			set_include_path($this->resolvePath('modules', $module) . PATH_SEPARATOR . get_include_path());
			$handler = array_pop($handlers);
			$this->useMiddleware($handlers);
			$this->useMiddleware('handler', function() use ($handler) {
				if(is_string($handler))
				{
					$handler = $this->handlers->__get($handler);
				}

				$realHandler = function() use ($handler) {
					try
					{
						return Closure::apply($handler, $this->params, $this);
					}
					catch(exceptions\ArgumentMissingException $e)
					{
						$this->response->notFound($e->getMessage());
					}
				};

				if($middlewares = $this->processDocString($handler))
				{
					$this->useMiddleware($middlewares);
					$this->useMiddleware('realHandler', $realHandler);
				}
				else
				{
					return $realHandler();
				}
			});
			$this->runAllMiddlewares();
		}
		else
		{
			$this->response->notFound();
		}
	}

	public function options($options)
	{
		IS_CLI and $this->router->options($options);
		return $this;
	}

	public function resolvePath()
	{
		return $this->config->basedir.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, Data::flatten(func_get_args()));
	}

	private function processDocString($handler)
	{
		$doc = Closure::parseDoc($handler);
		$middlewares = [];
		foreach($doc as $key => $lines)
		{
			$middlewares[] = $key . ':' . $lines[0];
		}
		return $middlewares;
	}

	private function runAllMiddlewares()
	{
		$response = '';
		$middlewares = [];
		while($middleware = array_shift($this->_middlewares))
		{
			list($middleware, $params) = $middleware;
			if(!is_null($result = $this->middlewares->__call($middleware, $params)))
			{
				if($result instanceof \Closure)
				{
					$middlewares[] = $result;
				}
				else
				{
					$response = $result;
					break;
				}
			}
		}
		$this->response->body = $response;
		if ($middlewares)
		{
			while($middleware = array_pop($middlewares))
			{
				$middleware($this->response);
			}
		}
	}

	private function runMiddlewares($middlewares)
	{
		foreach($middlewares as $middleware)
		{
			list($middleware, $params) = $middleware;
			if(!is_null($result = $this->middlewares->__call($middleware, $params)))
			{
				return $result;
			}
		}
	}

	private function prepareMiddlewares($middlewares)
	{
		return array_map(function($middleware) {
			if (strpos($middleware, ':')) // false or larger than 0
			{
				list($middleware, $params) = explode(':', $middleware);
				return [$middleware, explode(',', $params)];
			}
			return [$middleware, []];
		}, $middlewares);
	}

	public function header($key, $value=null)
	{
		$this->response->header($key, $value);
		return $this;
	}

	public function status($code)
	{
		$this->response->status($code);
		return $this;
	}

	public function send($body=null)
	{
		if($body)
		{
			$this->response->body = $body;
		}
		$this->response->send();
	}

	public function log($msg)
	{
		$toString = function($object)
		{
			if(is_string($object))
			{
				return $object;
			}
			elseif(is_array($object) || $object instanceof JsonSerializable || $object instanceof stdClass)
			{
				return json_encode($object, JSON_UNESCAPED_UNICODE);
			}
			else
			{
				return var_export($object, true);
			}
		};

		if(func_num_args() > 1)
		{
			$msg = vsprintf($msg, array_map($toString, array_slice(func_get_args(), 1)));
		}
		else
		{
			$msg = $toString($msg);
		}
		$this->response->stderr($msg . PHP_EOL);
	}

	public function __toString()
	{
		return 'App';
	}
}
