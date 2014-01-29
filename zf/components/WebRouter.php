<?php

namespace zf\components;

use Closure;

class WebRouter
{
	private $rules = [];
	private $module;
	public $params = [];

	public function __construct($request)
	{
		$this->method = $request->method;
		$this->path = $request->path;
		$this->segments = $request->segments;
		$this->base = '/' . $request->segments[0];
	}

	public function module($module)
	{
		$this->module = $module;
	}

	public function bulk($rules)
	{
		foreach($rules as $rule)
		{
			list($method, $path, $handler) = $rule;
			$this->append($method, $path, $handler);
		}
	}

	public function append($method, $pattern, $handler)
	{
		$this->rules[] = [strtoupper($method), $pattern, $handler, $this->module];
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

	public function debug()
	{
		$ret = [];
		foreach ($this->rules as $rule)
		{
			list($method, $pattern, $handler, $module) = $rule;
			$ret[] = "$method $pattern " . (($handler instanceof Closure) ? 'Closure' : $handler);
		}
		return $ret;
	}

	public function dispatch()
	{
		$baseLength = count($this->base);
		foreach ($this->rules as $rule)
		{
			list($method, $pattern, $handler, $module) = $rule;

			if (strncmp('/:', $pattern, 2) && strncmp($pattern, $this->base, $baseLength)) continue;

			if ($method === 'ANY' || $method === $this->method)
			{
				if ($this->path === $pattern)
				{
					return [$handler, null, $module];
				}
				elseif(false !== strpos($pattern, '/:'))
				{
					if ($params = $this->match($pattern, $this->path))
					{
						$this->params = $params;
						return [$handler, $params, $module];
					}
				}
			}
		}
		return [null, null, null];
	}

}
