<?php

namespace zf;

trait Request
{
	public $params;
	public $queryVars;
	public $requestMethod;
	public $requestBody;

	public function getstdin()
	{
		$ret = '';
		while(!feof(STDIN))
		{
			$ret .= fgets(STDIN);
		}
		return $ret;
	}

	public function isCli()
	{
		return 'cli' == PHP_SAPI;
	}

	public function getQuery($name,$default=null)
	{
		return isset($this->queryVars[$name]) ? $this->queryVars[$name] : $default;
	}

	public function getParam($name,$default=null)
	{
		return isset($this->requestBody[$name]) ? $this->requestBody[$name] : $default;
	}

	private function processRequestParams()
	{
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

}
