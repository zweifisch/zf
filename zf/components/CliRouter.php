<?php

namespace zf\components;

use zf\Reflection;
use zf\Data;

class CliRouter
{
	public $rules = [];
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

	public function append($method, $pattern, $handler)
	{
		if('cmd' === $method) $this->rules[] = [$pattern, $handler, $this->module];
	}

	public function parse($args)
	{
		$positionalArgs = [];
		$options = [];
		foreach($args as $arg)
		{
			if(!strncmp('--', $arg, 2))
			{
				if($optname = strstr($arg, '=', true))
				{
					$value = substr(strstr($arg, '='), 1);
				}
				else
				{
					$optname = $arg;
					$value = true;
				}
				$camelName = lcfirst(implode('', array_map('ucfirst', explode('-', $optname))));
				$options[$camelName] = $value;
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
			list($pattern, $handler, $module) = $rule;
			$params = $this->match($positionalArgs, $pattern);
			if($params !== false)
			{
				$this->params = array_merge($params, $options);
				return [$handler, $this->params, $module];
			}
		}
	}

	public function help()
	{
		$buffer[] = "Usage:\n";

		foreach($this->rules as $rule)
		{
			list($pattern, $handler, $module) = $rule;
			$buffer[] = "  php {$_SERVER['argv'][0]} $pattern";
			$params = array_filter(Reflection::parseDoc($handler), function($doc) {
				return $doc[0] === 'param';
			});

			$lines = array_map(function($param) use ($pattern) {
				list($type, $name, $comment) = explode(' ', $param[1], 3);
				$name = ltrim($name, '$');
				if (false === strpos($pattern, "<$name>")) {
					$name = '--' . implode('-', array_map('lcfirst',
						preg_split('/(?=[A-Z])/', $name)));
				}
				return [$name, $comment];
			}, $params);

			foreach($this->column($lines) as $line) {
				$buffer[] = "      $line";
			}

			$buffer[] = '';
		}
		return implode("\n", $buffer);
	}

	private function column($rows)
	{
		$cols = Data::transform($rows);
		$lens = array_map(function($col) {
			return max(array_map('strlen', $col));
		}, $cols);
		return array_map(function($row) use ($lens) {
			return implode("\t", array_map('str_pad', $row, $lens));
		},$rows);
	}
}
