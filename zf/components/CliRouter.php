<?php

namespace zf\components;

class CliRouter
{
	private $rules = [];
	private $options;
	private $module;

	public function __construct($request)
	{
		$this->argv = $request->argv;
	}

	public function module($module)
	{
		$this->module = $module;
	}

	public function append($method, $pattern, $handlers)
	{
		if('cmd' == $method) $this->rules[] = [$pattern, $handlers, $this->module];
	}

	public function options($options)
	{
		$opts = [];
		foreach($options as $key=>$value)
		{
			is_int($key) ? $opts[$value] = false : $opts[$key] = $value;
		}
		$this->options[count($this->rules) - 1] = $opts;
	}

	private function parse($args)
	{
		$positionalArgs = [];
		$options = [];
		foreach($args as $arg)
		{
			if(!strncmp('--', $arg, 2))
			{
				if($optname = strstr($arg, '=', true))
				{
					$options[substr($optname, 2)] = substr(strstr($arg, '='), 1);
				}
				else
				{
					$options[substr($arg, 2)] = true;
				}
			}
			else
			{
				$positionalArgs[] = $arg;
			}
		}
		return [$positionalArgs, $options];
	}

	private function match($positionalArgs, $pattern)
	{
		$pos = 0;
		$ret = [];
		foreach(explode(' ', $pattern) as $item)
		{
			if(!strncmp('<', $item, 1))
			{
				$name = substr($item, 1, strlen($item) - 2);
				{
					if(!isset($positionalArgs[$pos])) return false;
					$ret[$name] = $positionalArgs[$pos];
				}
			}
			else
			{
				if(empty($positionalArgs[$pos]) || $item !== $positionalArgs[$pos]) return false;
			}
			$pos ++;
		}
		return count($positionalArgs) == $pos ? $ret : false;
	}

	public function dispatch()
	{
		list($positionalArgs, $options) = $this->parse($this->argv);

		foreach($this->rules as $idx => $rule)
		{
			list($pattern, $handlers, $module) = $rule;
			$params = $this->match($positionalArgs, $pattern);
			if($params !== false)
			{
				$this->params = isset($this->options[$idx])
					? array_merge($this->options[$idx], $params, $options)
					: array_merge($params, $options);
				return [$handlers, $this->params, $module];
			}
		}
	}

	public function cmds()
	{
		$cmds = [];
		foreach($this->rules as $idx=>$rule)
		{
			$options = isset($this->options[$idx]) ? $this->options[$idx] : [];
			$cmds[] = [$rule[0], $options];
		}
		return $cmds;
	}
}
