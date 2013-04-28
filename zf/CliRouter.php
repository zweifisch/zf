<?php

namespace zf;

class CliRouter
{
	private $rules = array();

	public function append($_, $rule)
	{
		$this->rules[] = $rule;
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
					$options[substr($optname,2)] = true;
				}
				$optname = $arg;
			}
			else
			{
				if ($optname)
				{
					$options[substr($optname,2)] = $arg;
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

	private function match($positionalArgs, $cmd)
	{
		if (count($positionalArgs) == count($cmd))
		{
			$ret = [];
			foreach($cmd as $idx => $name)
			{
				if (0 == strncmp('<', $name, 1))
				{
					$ret[substr($name, 1, strlen($name) - 2)] = $positionalArgs[$idx];
				}
				elseif($name !== $positionalArgs[$idx])
				{
					return false;
				}
			}
			return $ret;
		}
		return false;
	}

	public function run()
	{
		if (isset($_SERVER['argv']) && count($_SERVER['argv']) > 1)
		{
			list($positionalArgs, $options) = $this->parse(array_slice($_SERVER['argv'], 1));

			foreach($this->rules as $rule)
			{
				if (3 == count($rule))
				{
					list($cmd, $expectedOptions, $callable) = $rule;
				}
				else
				{
					list($cmd, $callable) = $rule;
					$expectedOptions = [];
				}
				$cmd = explode(' ', $cmd);
				$matched = $this->match($positionalArgs, $cmd);
				if ($matched !== false)
				{
					foreach($expectedOptions as $optname)
					{
						$optname = substr($optname, 2);
						$matched[$optname] = isset($options[$optname]) ?  $options[$optname] : false;
					}
					return [$callable, $matched];
				}
			}
		}
		return [false, false];
	}
}
