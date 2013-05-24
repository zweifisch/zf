<?php

namespace zf;

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

	public function send($code, $body='', $options=null)
	{
		is_int($code) or list($code, $body, $options) = [200, $code, $body];

		$options or $options = [];

		if(!is_string($body))
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

		$this->response($options);
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
		if ($this->isCli())
		{
			echo "Usage:\n\n";
			foreach ($this->router->cmds() as $cmd)
			{
				echo '  php ', $_SERVER['argv'][0], ' ' , $cmd, "\n";
			}
			exit(1);
		}
		else
		{
			$this->send(404);
		}
	}

	public function jsonp($body)
	{
		if(isset($_GET['callback']))
		{
			$callback = $_GET['callback'];
			$body = $this->config->pretty
				? json_encode($body, JSON_PRETTY_PRINT)
				: json_encode($body);
			$this->send("$callback && $callback($body)",[
				'type'    => 'text/javascript',
				'charset' => $this->config->charset,
			]);
		}
		else
		{
			$this->send($body);
		}
	}

	public function render($template, $context)
	{	
		$this->renderWithContext($template, $context, function($path){ include $path; });
		$this->send(200);
	}

	public function renderAsString($template, $context)
	{
		return $this->renderWithContext($template, $context, function($path){
			ob_start();
			include $path;
			$ret = ob_get_contents();
			ob_end_clean();
			return $ret;
		});
	}

	private function renderWithContext($template, $context, $renderer)
	{
		$path = $this->config->views . DIRECTORY_SEPARATOR . $template . $this->config->viewext;
		if (!is_readable($path)) throw new \Exception("template $template($path) not found");
		$renderer = $renderer->bindTo((object)$context);
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
}
