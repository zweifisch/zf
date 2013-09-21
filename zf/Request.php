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

	private function processRequestBody()
	{
		$this->query = function(){
			return (new FancyObject($_GET, $this->validators, $this->mappers))->setParent($this);
		};
		if ('GET' == $this->requestMethod) return;

		$this->body = function(){
			$contentType = isset($_SERVER['HTTP_CONTENT_TYPE']) ? $_SERVER['HTTP_CONTENT_TYPE'] : '';
			$ret = '';
			if (!strncmp($contentType,'application/json', 16))
			{
				$ret = json_decode(file_get_contents('php://input'));
			}
			elseif ($contentType == 'application/x-www-form-urlencoded')
			{
				'POST' == $this->requestMethod ? $ret = $_POST : parse_str(file_get_contents('php://input'), $ret);
			}
			elseif (!strncmp($contentType, 'multipart/form-data', 19))
			{
				$ret = array_merge($_POST, $_FILES);
			}
			// elseif ($contentType == 'application/x-msgpack')
			// {
			// 	$ret = msgpack_unpack(file_get_contents('php://input'));
			// }
			else
			{
				$ret = file_get_contents('php://input');
			}
			return (new \zf\FancyObject($ret, $this->validators, $this->mappers))->setParent($this);
		};
		return $this;
	}

	public function clientIP()
	{
		return isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
	}

}
