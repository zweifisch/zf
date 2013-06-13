<?php

namespace zf;

class CliRouter
{
	private $rules;
	private $defaults;

	public function append($_, $rule)
	{
		$this->rules[] = $rule;
		$this->defaults[] = [];
	}

	public function attach($defaults)
	{
		$this->defaults[count($this->rules) - 1] = $defaults;
	}

	private function parse($args)
	{
		$positionalArgs = [];
		$options = [];
		$optname = null;
		foreach($args as $arg)
		{
			if (0 == strncmp('--', $arg, 2))
			{
				if ($optname)
				{
					$options[$optname] = true;
				}
				$optname = $arg;
			}
			else
			{
				if ($optname)
				{
					$options[$optname] = $arg;
					$optname = null;
				}
				else
				{
					$positionalArgs[] = $arg;
				}
			}
		}

		if ($optname)
		{
			$options[substr($optname,2)] = true;
		}

		return [$positionalArgs, $options];
	}

	private function match($positionalArgs, $options, $pattern, $defaults)
	{
		$pos = 0;
		$ret = [];
		$optionName = null;
		foreach(explode(' ', $pattern) as $item)
		{
			if (0 == strncmp('<', $item, 1))
			{
				$name = substr($item, 1, strlen($item) - 2);
				if($optionName)
				{
					if (isset($options[$optionName]))
					{
						$ret[$name] = $options[$optionName];
					}
					elseif (isset($defaults[$name]))
					{
						$ret[$name] = $defaults[$name];
					}
					else
					{
						return false;
					}
					$optionName = null;
				}
				else
				{
					if (isset($positionalArgs[$pos]))
					{
						$ret[$name] = $positionalArgs[$pos];
						$pos ++;
					}
					else
					{
						return false;
					}
				}
			}
			elseif(0 == strncmp('--', $item, 2))
			{
				$optionName = $item;
			}
			else
			{
				if (empty($positionalArgs[$pos]) || $item !== $positionalArgs[$pos])
				{
					return false;
				}
				$pos ++;
			}
		}
		if (count($positionalArgs) == $pos)
		{
			return $ret;
		}
		return false;
	}

	public function dispatch($args)
	{
		list($positionalArgs, $options) = $this->parse($args);

		foreach($this->rules as $idx => $rule)
		{
			list($pattern, $callable) = $rule;
			$params = $this->match($positionalArgs, $options, $pattern, $this->defaults[$idx]);
			if ($params !== false)
			{
				return [$callable, $params];
			}
		}
	}

	public function run()
	{
		if (isset($_SERVER['argv']) && count($_SERVER['argv']) > 1)
		{
			return $this->dispatch(array_slice($_SERVER['argv'], 1));
		}
		return [false, false];
	}

	public function cmds()
	{
		$cmds = [];
		foreach ($this->rules as $rule)
		{
			$cmds[] = $rule[0];
		}
		return $cmds;
	}
}
