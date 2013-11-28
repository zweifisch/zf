<?php

namespace zf;

use Exception;
use FilesystemIterator;
use InvalidArgumentException;

class App extends Laziness
{
	use EventEmitter;

	private $_lastComponent;
	private $_middlewares;

	public $config;

	function __construct()
	{
		ob_start();
		parent::__construct();

		defined('IS_CLI') or define('IS_CLI', 'cli' == PHP_SAPI);

		$basedir = IS_CLI ? dirname(realpath($_SERVER['argv'][0])) : $_SERVER['DOCUMENT_ROOT'];
		set_include_path($basedir . PATH_SEPARATOR . get_include_path());

		$this->config = new Config;
		$this->config->set(require __DIR__ . DIRECTORY_SEPARATOR . 'config.php');
		$components = $this->config->components;
		$this->config->load('configs.php', true);
		if(getenv('ENV'))
		{
			$this->config->load('configs-'.getenv('ENV').'.php', true);
		}
		if(isset($this->config->components))
		{
			$this->set('components', $components + $this->config->components);
		}
		$this->set('basedir', $basedir);
		$this->rewriteConfig();

		$on_exception = function($exception) {
			if(!$this->emit('exception', $exception)) throw $exception;
		};
		set_exception_handler($on_exception->bindTo($this));

		$on_shutdown = function(){
			$this->emit('shutdown');
		};
		register_shutdown_function($on_shutdown->bindTo($this));

		$on_error = function(){
			$this->response->stderr();
			return $this->emit('error', (object)array_combine(
				['no','str','file','line','context'], func_get_args()));
		};
		set_error_handler($on_error->bindTo($this));

		$this->useMiddleware($this->get('use middlewares'));
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
		$signal = strtoupper($signal);
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

	public function resource($name, $subResources=null)
	{
		$pass = function() use ($name) {
			$this->pass($name.'/'.$this->params->action);
		};

		$this->router->bulk([
			['GET'    , "/$name"                , ["$name/index"]],
			['GET'    , "/$name/new"            , ["$name/new"]],
			['POST'   , "/$name"                , ["$name/create"]],
			['GET'    , "/$name/:$name"         , ["$name/show"]],
			['GET'    , "/$name/:$name/edit"    , ["$name/edit"]],
			['PUT'    , "/$name/:$name"         , ["$name/update"]],
			['PATCH'  , "/$name/:$name"         , ["$name/modify"]],
			['DELETE' , "/$name/:$name"         , ["$name/destroy"]],
			['POST'   , "/$name/:$name/:action" , [$pass]],
		]);

		if($subResources)
		{
			foreach($subResources as $res)
			{
				$this->router->bulk([
					['GET'    , "/$name/:$name/$res"               , ["$name/$res/index"]],
					['GET'    , "/$name/:$name/$res/new"           , ["$name/$res/new"]],
					['POST'   , "/$name/:$name/$res"               , ["$name/$res/create"]],
					['GET'    , "/$name/:$name/$res/:$res"         , ["$name/$res/show"]],
					['GET'    , "/$name/:$name/$res/:$res/edit"    , ["$name/$res/edit"]],
					['PUT'    , "/$name/:$name/$res/:$res"         , ["$name/$res/update"]],
					['PATCH'  , "/$name/:$name/$res/:$res"         , ["$name/$res/modify"]],
					['DELETE' , "/$name/:$name/$res/:$res"         , ["$name/$res/destroy"]],
					['POST'   , "/$name/:$name/$res/:$res/:action" , [$pass]],
				]);
			}
		}

		return $this;
	}

	public function set($name, $value=null)
	{
		1 == func_num_args()
			? $this->config->set($name)
			: $this->config->set($name, $value);
		return $this;
	}

	public function get($name)
	{
		if(1 == func_num_args())
		{
			return $this->config->$name;
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

	public function initialized($callback)
	{
		$component = $this->_lastComponent;
		$this->on('computed', function($data) use ($component, $callback){
			if($data['key'] == $component)
			{
				$callback($data['value']);
				return true;
			}
		});
	}

	public function pass($handlerName)
	{
		return $this->handlers->__call($handlerName);
	}

	public function rpc($path, $closureSet)
	{
		$this->post($path, function() use ($closureSet){
			$jsonRpc = new JsonRpc(isset($this->config->{'jsonrpc codes'}) ? $this->get('jsonrpc codes') : null);
			$_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';

			if(!$jsonRpc->parse($this->body->asRaw(null)))
			{
				return $jsonRpc->response();
			}

			$closureSet = new ClosureSet($this, $closureSet);
			$this->helper->register('error', function($code, $data=null) use ($jsonRpc){
				return $jsonRpc->error($code, $data);
			});

			foreach($jsonRpc->calls as $call)
			{
				if(!is_array($call))
				{
					return $jsonRpc->result(null, $call)->response();
				}

				list($method, $params, $id) = $call;

				if(!$closureSet->exists($method))
				{
					return $jsonRpc->result($id, $jsonRpc->methodNotFound())->response();
				}

				try
				{
					$handler = $closureSet->__get($method);
					$middlewares = $this->processDocString($handler);
					$result = null;
					if($middlewares)
					{
						$result = $this->runMiddlewares($this->prepareMiddlewares($middlewares));
					}
					if (!isset($result))
					{
						$result = Closure::apply($handler, $params, $this);
					}
				}
				catch (Exception $e)
				{
					$result = $jsonRpc->internalError((string)$e);
				}
				if($id) $jsonRpc->result($id, $result);
			}
			return $jsonRpc->response();
		});
	}

	public function run()
	{
		$handlers = $this->request->route();

		if($handlers)
		{
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
					catch(InvalidArgumentException $e)
					{
						$this->response->notFound();
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

	private function rewriteConfig()
	{
		if(isset($this->config->components))
		{
			$components = [];
			foreach($this->config->components as $key => $constructArgs)
			{
				if(is_int($key))
				{
					list($name, $class) = explode(':', $constructArgs);
					$components[$name] = ['class'=> $class, 'constructArgs'=> []];
				}
				else
				{
					list($name, $class) = explode(':', $key);
					$components[$name] = ['class'=> $class, 'constructArgs'=> $constructArgs];
				}
			}
			$this->config->set('components', $components);
		}
	}

	public function __toString()
	{
		return 'App';
	}

}
