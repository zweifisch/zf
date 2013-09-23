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
	private $_status = 200;
	private $_headers = [];

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
			$args += array_pop($args);
		}
		$this->header('Cache-Control', $args);
	}

	public function status($code)
	{
		$this->_status = $code;
	}

	public function send($code, $body='', $options=null)
	{
		is_int($code) or list($code, $body, $options) = [$this->_status, $code, $body];

		if(is_null($body)) $body = '';

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

	public function stderr()
	{
		$buffer = ob_get_contents();
		ob_end_clean();
		file_put_contents('php://stderr', $buffer, FILE_APPEND);
	}

	public function end($code, $body='', $options=null)
	{
		$this->stderr();
		$this->send($code, $body, $options);
	}

	public function response($response)
	{
		$code = $response['code'];
		header('HTTP/1.1 ' . $code . ' ' . $this->statuses[$code]);
		header('Status: ' . $code);
		header('Content-Type: ' . (empty($response['charset']) ? $response['type'] : $response['type'] . '; charset=' . $response['charset']));
		if($this->config->debug && is_array($this->debug))
		{
			header('X-ZF-Debug: ' . json_encode($this->debug));
		}
		$this->sendHeader();
		exit($response['body']);
	}

	public function notFound()
	{
		if($this->isCli)
		{
			$this->_router->cmds() or exit(1);
			echo "Usage:\n\n";
			foreach($this->_router->cmds() as $cmd)
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
			$this->end(404);
		}
	}

	public function redirect($url, $permanent=false)
	{
		header('Location: ' . $url, true, $permanent ? 301 : 302);
		exit;
	}

	public function header($name, $value=null)
	{
		if(is_int($name))
		{
			$this->_status = $name;
		}
		else
		{
			$this->_headers[$name] = $value;
		}
	}

	public function sendHeader()
	{
		foreach($this->_headers as $name => $value)
		{
			if(is_array($value))
			{
				foreach($value as $k=>$v)
				{
					$options[] = is_int($k) ? $v : $k.'='.$v;
				}
				$value = implode(', ', $options);
			}
			header(is_null($value) ? $name : $name. ': '. $value);
		}
	}

	public function render($template, $vars=null)
	{
		return $this->engines->__call($this->get('view engine'), [$template, $vars]);
	}

	public function download($content, $name, $size=null)
	{
		header('Cache-Control: public, must-revalidate');
		header('Pragma: hack');
		header('Content-Type: application/octet-stream');
		if($size) header('Content-Length: ' . $size);
		header('Content-Disposition: attachment; filename="'.$name.'"');
		header("Content-Transfer-Encoding: binary\n");
		exit($content);
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
