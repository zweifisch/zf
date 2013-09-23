<?php

namespace zf;

use Phar;
use Exception;
use ReflectionClass;
use FilesystemIterator;
use InvalidArgumentException;

const EVENT_EXCEPTION = 'event_exception';
const EVENT_ERROR = 'event_error';
const EVENT_SHUTDOWN = 'event_shutdown';
const EVENT_VALIDATION_ERROR = 'event_validation_error';

class App extends Laziness
{
	use Request;
	use Response;
	use EventEmitter;

	private $_router;
	private $_eagerParams = [];
	private $_lastComponent;

	function __construct()
	{
		ob_start();
		parent::__construct();

		$this->isCli = 'cli' == PHP_SAPI;
		$basedir = $this->isCli ? dirname(realpath($_SERVER['argv'][0])) : $_SERVER['DOCUMENT_ROOT'];
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

		$this->_router = $this->isCli ? new CliRouter() : new Router();

		$on_exception = function($exception) {
			if(!$this->emit(EVENT_EXCEPTION, $exception)) throw $exception;
		};
		set_exception_handler($on_exception->bindTo($this));

		$on_shutdown = function(){
			$this->emit(EVENT_SHUTDOWN);
		};
		register_shutdown_function($on_shutdown->bindTo($this));

		$on_error = function(){
			$this->stderr();
			return $this->emit(EVENT_ERROR, (object)array_combine(
				['no','str','file','line','context'], func_get_args()));
		};
		set_error_handler($on_error->bindTo($this));
	}

	function __call($name, $args)
	{
		if(in_array($name, ['post', 'put', 'delete', 'patch', 'head', 'cmd'], true))
		{
			$pattern = array_shift($args);
			$this->_router->append($name, $pattern, $args);
		}
		elseif($this->helper->registered($name))
		{
			return $this->helper->__call($name, $args);
		}
		elseif($this->isCli && !strncmp('sig', $name, 3))
		{
			$name = strtoupper($name);
			if(defined($name))
			{
				pcntl_signal(constant($name), $args[0]->bindTo($this));
			}
			else
			{
				throw new Exception("signal \"$name\" not found");
			}
		}
		else
		{
			throw new Exception("method \"$name\" not found");
		}
		return $this;
	}

	public function __get($name)
	{
		if(!isset($this->$name) && isset($this->config->components) && isset($this->config->components[$name]))
		{
			$this->register($name); 
		}
		return parent::__get($name);
	}

	public function resource($name, $subResources=null)
	{
		$pass = function() use ($name){
			$this->pass($name.'/'.$this->params->action);
		};

		$this->_router->bulk([
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
				$this->_router->bulk([
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
			$this->_router->append('GET', $pattern, $args);
			return $this;
		}
	}

	public function param($name, $handler=null)
	{
		$this->_paramsTmp = $this->paramHandlers->register($name, $handler);
		return $this;
	}

	public function eager()
	{
		if(is_array($this->_paramsTmp))
		{
			1 == count($this->_paramsTmp)
				? $this->_eagerParams[] = $this->_paramsTmp[0]
				: $this->_eagerParams = array_merge($this->_eagerParams, $this->_paramsTmp);
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
		$this->handlers->register($name, $closure);
		return $this;
	}

	public function middleware($name, $closure)
	{
		$this->middlewares->register($name, $closure);
		return $this;
	}

	public function validator($name, $closure)
	{
		$this->validators->register($name, $closure);
		return $this;
	}

	public function map($type, $closure=null)
	{
		$this->mappers->register($type, $closure);
		return $this;
	}

	public function register($name, $component=null)
	{
		$this->_lastComponent = $name;
		if($component instanceof \Closure)
		{
			$this->$name = $component;
		}
		else
		{
			$constructArgs = array_slice(func_get_args(), 2);
			$this->$name = function() use ($component, $constructArgs, $name){
				if(empty($constructArgs) && isset($this->config->components) && isset($this->config->components[$name]))
				{
					$constructArgs = $this->config->components[$name]['constructArgs'];
					$component = $this->config->components[$name]['class'];
				}
				$constructArgs = array_map(function($arg){
					return $arg instanceof \Closure ? $arg() : $arg;
				}, $constructArgs);
				return is_array($constructArgs) && !is_assoc($constructArgs)
					? (new ReflectionClass($component))->newInstanceArgs($constructArgs)
					: (new ReflectionClass($component))->newInstance($constructArgs);
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
			if($jsonRpc->parse($this->body->asArray(null)))
			{
				$closureSet = new ClosureSet($this, $closureSet);
				$this->helper->register('error', function($code, $data=null) use ($jsonRpc){
					return $jsonRpc->error($code, $data);
				});
				foreach($jsonRpc->calls as $call)
				{
					if(is_array($call))
					{
						list($method, $params, $id) = $call;
						if($closureSet->exists($method))
						{
							$result = $closureSet->__apply($method, $params);
							if($id) $jsonRpc->result($id, $result);
						}
						else
						{
							$jsonRpc->result($id, $jsonRpc->methodNotFound());
						}
					}
					else
					{
						$jsonRpc->result(null, $call);
					}
				}
			}
			return $jsonRpc->response();
		});
	}

	public function run()
	{
		$this->requestMethod = $this->isCli ? 'CLI' : strtoupper($_SERVER['REQUEST_METHOD']);

		if($this->isCli)
		{
			$this->phar();
		}

		list($handlers, $params) = $this->_router->run();
		if($handlers)
		{
			$this->params = new Laziness($params, $this);
			if($params) $this->processParamsHandlers($params);

			if(!$this->isCli)
			{
				if($_GET)
				{
					$this->processParamsHandlers($_GET);
					$params = $params ? $params + $_GET : $_GET;
				}
				$this->processRequestBody();
			}

			$handler = array_pop($handlers);

			if($handlers)
			{
				foreach($handlers as $middleware)
				{
					$_params= [];
					if(2 == count($exploded = explode(':', $middleware)))
					{
						list($middleware, $_params) = $exploded;
						$_params = explode(',', $_params);
					}
					$middlewares[$middleware] = $_params;
				}
				$this->runMiddlewares($middlewares);
			}

			try
			{
				$this->end($this->runHandler($handler, $params));
			}
			catch(InvalidArgumentException $e)
			{
				$this->notFound();
			}
		}
		else
		{
			$this->notFound();
		}
	}

	public function options($options)
	{
		if($this->isCli) $this->_router->options($options);
		return $this;
	}

	public function path()
	{
		return $this->config->basedir.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, func_get_args());
	}

	private function runHandler($handler, $params)
	{
		if(is_string($handler))
		{
			$handler = $this->handlers->__get($handler);
		}

		$doc = Closure::parseDoc($handler);
		foreach($doc as $key => $lines)
		{
			$middlewares[$key] = explode(',', $lines[0]);
		}
		if(isset($middlewares)) $this->runMiddlewares($middlewares);
		return Closure::apply($handler, $params, $this);
	}

	private function runMiddlewares($middlewares)
	{
		foreach($middlewares as $middleware => $params)
		{
			if($response = $this->middlewares->__call($middleware, $params))
			{
				$this->end($response);
			}
		}
	}

	private function processParamsHandlers($input)
	{
		foreach($input as $name => $value)
		{
			if ($this->paramHandlers->registered($name))
			{
				$args = [$value];
				$this->params->$name = in_array($name, $this->_eagerParams, true)
					? $this->paramHandlers->__call($name, $args)
					: $this->paramHandlers->delayed->__call($name, $args);
			}
		}
	}

	private function phar()
	{
		if('.phar' == substr($_SERVER['SCRIPT_FILENAME'], -5) && $this->config->extract)
		{
			$this->cmd('extract <path>', function(){
				try {
					$phar = new Phar($_SERVER['SCRIPT_FILENAME']);
					$phar->extractTo($this->params->path, null, true);
				} catch (Exception $e) {
					echo $e->getMessage();
					exit(1);
				}
			});
		}
		elseif($this->config->dist)
		{
			$this->cmd('dist <name>', function(){
				$entryScript = basename($_SERVER['SCRIPT_FILENAME']);
				$phar = new Phar($this->params->name, FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME, $this->params->name);
				$phar->buildFromDirectory($this->config->basedir, '/\.php$/');
				$phar->setStub($phar->createDefaultStub($entryScript));
			});
		}
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

function is_assoc($array)
{
	return (bool)count(array_filter(array_keys($array), 'is_string'));
}

