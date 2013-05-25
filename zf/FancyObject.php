<?php

namespace zf;

class FancyObject implements \JsonSerializable
{
	use EventEmitter;
	private $root;
	private $context;
	private $path;
	private $usedValidators;

	function __construct($root, $validators, $mappers)
	{
		$this->root = is_array($root) ? $root : [];
		$this->usedValidators = [];
		$this->path = [];
		$this->validators = $validators;
		$this->mappers = $mappers;
	}

	function __get($name)
	{
		$this->path[] = $name;
		return $this;
	}

	function __call($name, $args)
	{
		if (0 == strncmp('as', $name, 2))
		{
			return $this->getAs(substr($name, 2), $args);
		}
		$this->usedValidators[$name] = $this->validators->__call($name, $args);
		return $this;
	}

	public function jsonSerialize()
	{
		return $this->root;
	}

	private function getAs($type, $default)
	{
		$required = empty($default);
		list($isset, $value) = $this->get($required);
		if(!$isset)
		{
			if($required)
			{
				return $this->done(null);
			}
			else
			{
				$value = $default[0];
			}
		}
		$mappedValue = $this->map($type, $value, implode('.', $this->path));
		if(is_null($mappedValue))
		{
			$this->emit('validation:failed', ['validator'=> $type, 'input'=> $value, 'key'=> implode('.', $this->path)]);
			return $this->done(null);
		}
		return $this->validate($mappedValue) ? $this->done($mappedValue): $this->done(null);
	}

	private function done($ret)
	{
		$this->path = [];
		$this->usedValidators = [];
		return $ret;
	}

	private function map($type, $value, $path)
	{
		return $this->mappers->__call($type, [$value, $path]);
	}

	private function get($required)
	{
		$cursor = $this->root;
		foreach ($this->path as $path)
		{
			if(isset($cursor[$path]))
			{
				$cursor = $cursor[$path];
			}
			else
			{
				if ($required)
				{
					$this->emit('validation:failed', ['validator'=> 'required', 'input'=>null, 'key'=> implode('.', $this->path)]);
				}
				$this->path = [];
				return [false, null];
			}
		}
		return [true, $cursor];
	}

	private function validate($value, $preserveRules=false)
	{
		foreach($this->usedValidators as $name => $validator)
		{
			if(!$validator($value))
			{
				$preserveRules or $this->usedValidators = [];
				$this->emit('validation:failed', ['validator'=> $name, 'input'=> $value, 'key'=> implode('.', $this->path)]);
				return false;
			}
		}
		$preserveRules or $this->usedValidators = [];
		return true;
	}

}
