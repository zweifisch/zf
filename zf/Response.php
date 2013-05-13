<?php

namespace zf;

trait Response
{
	public function lastModified($time)
	{
		header('Last-Modified: '. gmdate(DATE_RFC2822, $time));
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $time)
		{
			 header('HTTP/1.0 304 Not Modified');
			 exit;
		}
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
			$body = $this->config->pretty
				? json_encode($body, JSON_PRETTY_PRINT)
				: json_encode($body);
			$type = 'application/json';
		}

		$this->response([ 'body' => $body, 'code' => $code, 'type' => $type ]);
	}

	public function response($response)
	{
		header('HTTP/1.1: '. $response['code']);
		header('Status: '. $response['code']);
		header('Content-Type: '. $response['type']);
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
}
