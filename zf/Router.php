<?php

namespace zf;

class Router
{
	private $rules = [];

	public function append($method, $rule)
	{
		$this->rules[strtoupper($method)][] = $rule; #  defaultdict? what's that?
	}

	public function parse($pattern)
	{
		$count = preg_match_all('/:([^\/?]+)/', $pattern, $names);
		$regexp = preg_replace('(\/:[^\\/?]+)', '(?:/([^/?]+))', $pattern);
		$regexp = '/^'.str_replace('/','\/', $regexp).'$/';
		return [$names[1], $regexp, $count];
	}

	public function dispatch($method, $path)
	{
		if(isset($this->rules[$method]))
		{
			foreach($this->rules[$method] as $rule)
			{
				list($pattern, $callback) = $rule;
				list($names, $regexp, $count) = $this->parse($pattern);
				if($count > 0)
				{
					$matched = preg_match($regexp, $path, $values);
					if($matched > 0)
					{
						array_shift($values);
						if(count($names) > count($values))
						{
							array_pop($names); #  last param can be optional
						}
						return [$callback, array_combine($names, $values)];
					}
				}
				else
				{
					if ($path === $pattern) # static
					{
						return [$callback,array()];
					}
				}
			}
		}
		return [false,false];
	}

	public function run()
	{
		$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : $_SERVER['REQUEST_URI'];
		$pos = strpos($path, '?');
		if ($pos)
		{
			$path = substr($path,0,$pos);
		}
		if (strtolower(substr($path, -9)) == 'index.php')
		{
			$path = '/';
		}
		return $this->dispatch(strtoupper($_SERVER['REQUEST_METHOD']), $path);
	}
}
