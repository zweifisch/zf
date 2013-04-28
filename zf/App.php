<?php

namespace zf;

class App
{

	private $paramHandlers = [];
	private $nsPrefix = '\\';
	private $router;
	private $components;
	public $params;
	public $queryVars;
	public $requestMethod;
	public $requestBody;
	public $config;
	private static $instance;

	function __construct()
	{
		 $this->config = new Config();
		 if($this->is_cli())
		 {
			 $this->router = new CliRouter();
			 $this->requestMethod = 'CLI';
		 }
		 else
		 {
			 $this->router = new Router();
			 $this->requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);
		 }
		 self::$instance = $this;
	}

	function __call($name, $args)
	{
		if (in_array($name, ['get', 'post', 'put', 'delete', 'patch', 'cmd'], true))
		{
			if (3 == count($args))
			{
				list($pattern, $options, $callable) = $args;
			}
			else
			{
				list($pattern, $callable) = $args;
			}

			if(is_array($callable))
			{
				list($classname, $method) = $callable;
				$callable = [$this->nsPrefix . '\\' . $classname, $method];
			}

			if (3 == count($args))
			{
				$this->router->append($name, [$pattern, $options, $callable]);
			}
			else
			{
				$this->router->append($name, [$pattern, $callable]);
			}

			return $this;
		}
		elseif ($this->is_cli())
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
					throw new \Exception('Method not found');
				}
			}
		}
		else
		{
			throw new \Exception('Method not found');
		}
	}

	function __get($name)
	{
		if (isset($this->components[$name]))
		{
			return $this->getComponent($name);
		}
	}

	public static function getApp()
	{
		return self::$instance;
	}

	public function register($alias)
	{
		$args = func_get_args();
		array_shift($args);
		$this->components[$alias] = $args;
	}

	public function is_cli()
	{
		return 'cli' == PHP_SAPI;
	}

	public function param($name, $callable)
	{
		$this->paramHandlers[$name] = $callable;
		return $this;
	}

	public function getQuery($name,$default=null)
	{
		return isset($this->queryVars[$name]) ? $this->queryVars[$name] : $default;
	}

	public function getParam($name,$default=null)
	{
		return isset($this->requestBody[$name]) ? $this->requestBody[$name] : $default;
	}

	public function run()
	{
		list($callable, $params) = $this->router->run();

		if($callable)
		{
			$this->params = $params;
			$this->processParams();
			$this->params = (object)$this->params;

			$this->processRequestParams();

			if (is_array($callable))
			{
				list($classname, $method) = $callable;
				$this->call($classname, $method, [$this->params, $this]);
			}
			else
			{
				call_user_func($callable->bindTo($this), $this->params, $this);
			}
		}
		else
		{
			if ($this->is_cli())
			{
				exit(1);
			}
			else
			{
				$this->send(404);
			}
		}
	}

	public function ns($nsPrefix)
	{
		$this->nsPrefix = $nsPrefix;
		return $this;
	}

	public function send($code, $body='', $type='text/html')
	{
		if(!is_int($code))
		{
			$body = $code;
			$code = 200;
		}

		if(!is_string($body))
		{
			$body = json_encode($body);
			$type = 'application/json';
		}

		$this->response([ 'body' => $body, 'code' => $code, 'type' => $type ]);
	}

	public function response($response)
	{
		header('HTTP/1.1: ' . $response['code']);
		header('Status: ' . $response['code']);
		header('Content-Type: '. $response['type']);
		exit($response['body']);
	}

	public function jsonp($body)
	{
		if(isset($_GET['callback']))
		{
			$callback = $_GET['callback'];
			$body = json_encode($body);
			$this->response([
				'body' => "$callback && $callback($body)",
				'code' => '200',
				'type' => 'text/javascript',
			]);
		}
		else
		{
			$this->send($body);
		}
	}

	public function getstdin()
	{
		$ret = '';
		while(!feof(STDIN))
		{
			$ret .= fgets(STDIN);
		}
		return $ret;
	}

	private function processParams()
	{
		foreach($this->params as $name => $value)
		{
			if (isset($this->paramHandlers[$name]))
			{
				if(is_array($this->paramHandlers[$name]))
				{
					list($classname, $method) = $this->paramHandlers[$name];
					$this->params[$name] = $this->call(
						$this->nsPrefix.'\\'.$classname, $method, [$value, $this]);
				}
				else
				{
					$this->params[$name] = call_user_func(
						$this->paramHandlers[$name]->bindTo($this), $value, $this);
				}
			}
		}
	}

	private function processRequestParams()
	{
		if ($this->is_cli())
		{
			return;
		}
		$this->queryVars= $_GET;

		if('GET' == $this->requestMethod)
		{
			return;
		}

		$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'application/x-www-form-urlencoded';

		if($contentType == "application/json")
		{
			$this->requestBody = json_decode(file_get_contents('php://input'));
		}
		else if( $contentType == "application/x-www-form-urlencoded")
		{
			if ($this->requestMethod == 'POST')
			{
				$this->requestBody = $_POST;
			}
			else
			{
				parse_str(file_get_contents('php://input'), $this->requestBody);
			}
		}
	}

	private function call($classname, $method, $args)
	{
		$classnameReflection = new \ReflectionClass($classname);
		$methodReflection = $classnameReflection->getMethod($method);
		if($methodReflection->isStatic())
		{
			return $methodReflection->invokeArgs(null,$args);
		}
		else if($methodReflection->isPublic())
		{
			$instance = $classnameReflection->newInstanceArgs();
			return $methodReflection->invokeArgs($instance,$args);
		}
	}

	private function getInstance($classname, $constructArgs=null)
	{
		$classnameReflection = new \ReflectionClass($classname);
		if (is_null($constructArgs))
		{
			return $classnameReflection->newInstanceArgs();
		}
		return $classnameReflection->newInstanceArgs($constructArgs);
	}

	private function getComponent($name)
	{
		if (is_array($this->components[$name]))
		{
			if (2 == count($this->components[$name]))
			{
				list($className, $constructArgs) = $this->components[$name];
				$this->components[$name] = $this->getInstance($className, [$constructArgs]);
			}
			else
			{
				$this->components[$name] = $this->getInstance($this->components[$name][0]);
			}
		}
		return $this->components[$name];
	}

}
