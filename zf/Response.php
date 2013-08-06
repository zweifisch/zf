<?php

namespace zf;

use JsonSerializable;
use stdClass;

trait Response
{ 
	private $debug;
	private $statuses = [
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported'
	];
	private $status = 200;

	public function lastModified($time)
	{
		header('Last-Modified: '. gmdate(DATE_RFC2822, $time));
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $time)
		{
			 header('HTTP/1.0 304 Not Modified');
			 exit;
		}
	}

	public function cacheControl()
	{
		$args = func_get_args();
		if(is_array(end($args)))
		{
			$options = array_pop($args);
			foreach($options as $key=>$value)
			{
				$args[] = $key.'='.$value;
			}
		}
		header('Cache-Control: '. implode(', ', $args));
	}

	public function status($code)
	{
		$this->status = $code;
	}

	public function send($code, $body='', $options=null)
	{
		is_int($code) or list($code, $body, $options) = [$this->status, $code, $body];

		$options or $options = [];

		if(!is_string($body) && empty($options['type']))
		{
			$body = $this->config->pretty
				? json_encode($body, JSON_PRETTY_PRINT)
				: json_encode($body);
			$options['type'] = 'application/json';
			$options['charset'] = $this->config->charset;
		}

		if(empty($options['type']))
		{
			$options['type'] = 'text/html';
			$options['charset'] = $this->config->charset;
		}

		$options['body'] = $body;
		$options['code'] = $code;

		if(isset($this->config->jsonp) && !empty($_GET[$this->config->jsonp]))
		{
			$callback = $_GET[$this->config->jsonp];
			$options['body'] = "$callback && $callback({$options['body']})";
			$options['type'] = 'text/javascript';
		}

		$this->response($options);
	}

	public function end($code, $body='', $options=null)
	{
		$this->send($code, $body, $options);
	}

	public function response($response)
	{
		$code = $response['code'];
		header("HTTP/1.1 $code {$this->statuses[$code]}");
		header("Status: $code");
		empty($response['charset'])
			? header("Content-Type: ${response['type']}")
			: header("Content-Type: ${response['type']}; charset=${response['charset']}");
		if($this->config->debug && is_array($this->debug))
		{
			header('X-ZF-Debug: '.json_encode($this->debug));
		}
		exit($response['body']);
	}

	public function notFound()
	{
		if ($this->isCli)
		{
			echo "Usage:\n\n";
			foreach ($this->_router->cmds() as $cmd)
			{
				list($cmd, $options) = $cmd;
				echo '  php ', $_SERVER['argv'][0], ' ' , $cmd, "\n";
				foreach($options as $option=>$default)
				{
					if(is_bool($default))
					{
						echo "      --$option\n";
					}
					else
					{
						echo "      --$option\tdefault: $default\n";
					}
				}
				echo "\n";
			}
			exit(1);
		}
		else
		{
			$this->send(404);
		}
	}

	public function redirect($url, $permanent=false)
	{
		header('Location: ' . $url, true, $permanent ? 301 : 302);
		exit();
	}

	public function render($template, $vars=null)
	{
		$this->send(200, $this->renderAsString($template,$vars));
	}

	public function renderAsString($template, $vars=null)
	{
		return $this->renderWithContext($template, function($path) use ($vars){
			if($vars) extract($vars);
			ob_start();
			include $path;
			$ret = ob_get_contents();
			ob_end_clean();
			return $ret;
		});
	}

	private function renderWithContext($template, $renderer)
	{
		$path = $this->config->views . DIRECTORY_SEPARATOR . $template . $this->config->viewext;
		if(!stream_resolve_include_path($path)) throw new \Exception("template $template($path) not found");
		$renderer = $renderer->bindTo($this);
		return $renderer($path);
	}

	public function debug($msg, $object)
	{
		if($this->config->debug){
			list($bt) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,1);
			$this->debug[] = [
				$msg,
				$object,
				basename($bt['file']),
				$bt['line'],
			];
		}
		return $this;
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
		echo $msg, PHP_EOL;
	}
}
