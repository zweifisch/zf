<?php

namespace zf;

class Router
{
	private $rules = [];

	public function append($method, $pattern, $handlers)
	{
		$this->rules[strtoupper($method)][] = [$pattern, $handlers];
	}

	public function bulk($rules)
	{
		foreach($rules as $rule)
		{
			list($method, $path, $handlers) = $rule;
			$this->append($method, $path, $handlers);
		}
	}

	public function parse($pattern)
	{
		preg_match_all('/:([^\/?]+)/', $pattern, $names);
		$regexp = preg_replace(['(\/[^:\\/?]+)','(\/:[^\\/?\\(]+)'], ['(?:\0)','(?:/([^/?]+))'], $pattern);
		return [$names[1], '/^'.str_replace('/','\/', $regexp).'$/'];
	}

	public function match($pattern, $path)
	{
		list($names, $regexp) = $this->parse($pattern);
		if(preg_match($regexp, $path, $values))
		{
			foreach($names as $idx=>$name)
			{
				$params[$name] = isset($values[$idx+1]) ? $values[$idx+1] : null;
			}
			return $params;
		}
	}

	public function dispatch($method, $path)
	{
		if(!isset($this->rules[$method])) return [null, null];

		foreach($this->rules[$method] as $rule)
		{
			list($pattern, $handlers) = $rule;

			if(false === strpos($pattern, '/:')) # static pattern
			{
				if($path === $pattern)
				{
					return [$handlers, null];
				}
			}
			else
			{
				$staticPrefix = strstr($pattern, '/:', true);
				if(!$staticPrefix || !strncmp($staticPrefix, $path, strlen($staticPrefix)))
				{
					if($params = $this->match($pattern, $path))
					{
						return [$handlers, $params];
					}
				}
			}
		}
		return [null, null];
	}

	public function run()
	{
		$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : $_SERVER['REQUEST_URI'];
		if($pos = strpos($path, '?'))
		{
			$path = substr($path,0,$pos);
		}
		return $this->dispatch(strtoupper($_SERVER['REQUEST_METHOD']), $path);
	}
}
