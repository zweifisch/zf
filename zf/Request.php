<?php

namespace zf;

trait Request
{
	public $params;
	public $requestMethod;

	public function getstdin()
	{
		$ret = '';
		while(!feof(STDIN))
		{
			$ret .= fgets(STDIN);
		}
		return $ret;
	}

	public function clientIP()
	{
		return isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
	}

	public function server($key, $default=null)
	{
		return isset($_SERVER[$key]) ? $_SERVER[$key] : $default;
	}
}
