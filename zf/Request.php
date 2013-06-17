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

	private function processRequestBody($fancy)
	{
		$this->query = function() use ($fancy) {
			return $fancy ? (new FancyObject($_GET, $this->validators, $this->mappers))->setParent($this) : $_GET;
		};
		if ('GET' == $this->requestMethod) return;

		$contentType = isset($_SERVER['HTTP_CONTENT_TYPE']) ? $_SERVER['HTTP_CONTENT_TYPE'] : '';

		$this->body = function() use ($contentType, $fancy){
			$ret = '';
			if ($contentType == 'application/json')
			{
				$ret = json_decode(file_get_contents('php://input'), true);
			}
			elseif ($contentType == 'application/x-www-form-urlencoded')
			{
				'POST' == $this->requestMethod ? $ret = $_POST : parse_str(file_get_contents('php://input'), $ret);
			}
			elseif (0 == strncmp($contentType, 'multipart/form-data', 19))
			{
				$ret = array_merge($_POST, $_FILES);
			}
			else
			{
				$ret = file_get_contents('php://input');
			}
			return $fancy ? (new FancyObject($ret, $this->validators, $this->mappers))->setParent($this) : $ret;
		};
		return $this;
	}

}
