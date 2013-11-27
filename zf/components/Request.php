<?php

namespace zf\components;

class Request
{

	private $params;
	private $router;

	public function __construct($router)
	{
		$this->router = $router;
	}

	public function __get($key)
	{
		return call_user_func([$this, 'get' . ucfirst($key)]);
	}

	public function getIp()
	{
		return isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
	}

	public function getStdin()
	{
		$ret = '';
		while(!feof(STDIN))
		{
			$ret .= fgets(STDIN);
		}
		return $ret;
	}

	public function getBody()
	{
		return file_get_contents('php://input');
	}

	public function getContentType()
	{
		return isset($_SERVER['HTTP_CONTENT_TYPE']) ? $_SERVER['HTTP_CONTENT_TYPE'] : '';
	}

	public function getMethod()
	{
		return $this->method = IS_CLI ? 'CLI' : strtoupper($_SERVER['REQUEST_METHOD']);
	}

	public function getIsCli()
	{
		return IS_CLI;
	}

	public function getParams()
	{
		return $this->params;
	}

	public function route()
	{
		list($handlers, $params) = $this->router->run();
		if($_GET)
		{
			$params = $params ? array_merge($_GET, $params) : $_GET;
		}
		$this->params = (object)$params;
		return $handlers;
	}

	public function contentTypeMatches($type, $length=null)
	{
		$length or $length = strlen($type);
		return !strncmp($this->getContentType(), $type, $length);
	}

	public function server($key, $default=null)
	{
		return isset($_SERVER[$key]) ? $_SERVER[$key] : $default;
	}
}
