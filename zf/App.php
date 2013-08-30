<?php

namespace zf;

use Phar;
use Closure;
use Exception;
use ReflectionClass;
use FilesystemIterator;

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
		set_include_path(get_include_path() . PATH_SEPARATOR . $basedir);

		$this->config = new Config;
		$this->set(['nodebug', 'nopretty', 'fancy', 'nodist', 'noextract',
			'handlers'   => 'handlers',
			'helpers'    => 'helpers',
			'params'     => 'params',
			'views'      => 'views',
			'validators' => 'validators',
			'mappers'    => 'mappers',
			'viewext'    => '.php',
			'charset'    => 'utf-8',
			'basedir'    => $basedir,
			'view engine' => 'default',
		]);
		$this->config->load('configs.php', true);
		if(getenv('ENV'))
			$this->config->load('configs-'.getenv('ENV').'.php', true);

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
			return $this->emit(EVENT_ERROR, (object)array_combine(
				['no','str','file','line','context'], func_get_args()));
		};
		set_error_handler($on_error->bindTo($this));

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
		$this->engines = function(){
			$engines = new ClosureSet($this, $this->get('view engine'));
			$engines->register(require __DIR__ . DIRECTORY_SEPARATOR . 'engines.php');
			return $engines;
		};
	}

	function __call($name, $args)
	{
		if(in_array($name, ['post', 'put', 'delete', 'patch', 'head', 'cmd'], true))
		{
			$this->_router->append($name, $args);
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

	public function resource($name)
	{
		$pass = function() use ($name){
			$this->pass($name.'/'.$this->params->action);
		};

		$this->_router->bulk([
			['GET',    "/$name", "$name/index"],
			['POST',   "/$name", "$name/create"],
			['GET',    "/$name/:$name", "$name/show"],
			['PUT',    "/$name/:$name", "$name/update"],
			['PATCH',  "/$name/:$name", "$name/modify"],
			['DELETE', "/$name/:$name", "$name/destroy"],
			['GET',    "/$name/:$name/edit", "$name/edit"],
			['POST',   "/$name/:$name/:action", $pass],
		]);
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
			$this->_router->append('GET', func_get_args());
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
		$this->requestHandlers->register($name, $closure);
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

	public function register($alias, $component)
	{
		$this->_lastComponent = $alias;
		if($component instanceof Closure)
		{
			$this->$alias = $component;
		}
		else
		{
			$constructArgs = array_slice(func_get_args(), 2);
			$this->$alias = function() use ($component, $constructArgs, $alias){
				if(empty($constructArgs) && isset($this->config->$alias))
				{
					$constructArgs = $this->config->$alias;
				}
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
		return $this->requestHandlers->__call($handlerName);
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

		list($handler, $params) = $this->_router->run();
		if($handler)
		{
			if($this->config->fancy)
			{
				$this->validators->register(require __DIR__ . DIRECTORY_SEPARATOR . 'validators.php');
				$this->mappers->register(require __DIR__ . DIRECTORY_SEPARATOR . 'mappers.php');
			}
			$this->params = new Laziness($params, $this);
			if($params) $this->processParamsHandlers($params);
			if(!$this->isCli)
			{
				if($_GET) $this->processParamsHandlers($_GET);
				$this->processRequestBody($this->config->fancy);
			}
			if(is_string($handler))
			{
				$response = $this->requestHandlers->__call($handler);
			}
			else
			{
				$handler = $handler->bindTo($this);
				$response = $handler();
			}
			$this->end($response);
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

}

function is_assoc($array)
{
	return (bool)count(array_filter(array_keys($array), 'is_string'));
}
