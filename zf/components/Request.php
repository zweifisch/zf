<?php

namespace zf\components;

use zf\lazy\Getter;

class Request
{
	use Getter;

	public function getIp()
	{
		return isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
	}

	public function getPath()
	{
		return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	}

	public function getSegments()
	{
		return explode('/', substr($this->path, 1));
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

	public function getReferer()
	{
		return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
	}

	public function getMethod()
	{
		return $this->method = IS_CLI ? 'CLI' : $_SERVER['REQUEST_METHOD'];
	}

	public function getIsCli()
	{
		return IS_CLI;
	}

	public function getArgv()
	{
		return array_slice($_SERVER['argv'], 1);
	}

	public function contentTypeMatches($type, $length=null)
	{
		$length or $length = strlen($type);
		return !strncmp($this->getContentType(), $type, $length);
	}

}
