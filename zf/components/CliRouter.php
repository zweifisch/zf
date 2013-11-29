<?php

namespace zf\components;

class CliRouter
{
	private $rules;
	private $options;

	public function __construct()
	{
		$this->rules = [];
	}

	public function append($method, $pattern, $handlers)
	{
		if('cmd' == $method) $this->rules[] = [$pattern, $handlers];
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

	public function dispatch($args)
	{
		list($positionalArgs, $options) = $this->parse($args);

		foreach($this->rules as $idx => $rule)
		{
			list($pattern, $handlers) = $rule;
			$params = $this->match($positionalArgs, $pattern);
			if($params !== false)
			{
				return isset($this->options[$idx])
					? [$handlers, array_merge($this->options[$idx], $params, $options)]
					: [$handlers, array_merge($params, $options)];
			}
		}
	}

	public function run()
	{
		if(isset($_SERVER['argv']) && count($_SERVER['argv']) > 1)
		{
			return $this->dispatch(array_slice($_SERVER['argv'], 1));
		}
		return [false, false];
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
