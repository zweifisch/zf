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

	public function isCli()
	{
		return 'cli' == PHP_SAPI;
	}

	private function processRequestParams($fancy)
	{
		if ($fancy) \zf\FancyObject::setValidators($this->validators);

		$this->query = function() use ($fancy) {
			return $fancy ? (new \zf\FancyObject($_GET))->setParent($this) : $_GET;
		};
		if ('GET' == $this->requestMethod) return;

		$contentType = isset($_SERVER['HTTP_CONTENT_TYPE']) ? $_SERVER['HTTP_CONTENT_TYPE'] : '';

		$this->body = function() use ($contentType, $fancy){
			$ret = '';
			if ($contentType == "application/json")
			{
				$ret = json_decode(file_get_contents('php://input'), true);
			}
			elseif ($contentType == "application/x-www-form-urlencoded")
			{
				'POST' == $this->requestMethod ? $ret = $_POST : parse_str(file_get_contents('php://input'), $ret);
			}
			return $fancy ? (new \zf\FancyObject($ret))->setParent($this) : $ret;
		};
		return $this;
	}

}
