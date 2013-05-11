<?php

namespace zf;

class FancyObject implements \JsonSerializable
{
	use Closure;
	use EventEmitter;
	private $root;
	private $path;
	private $validators;
	private static $_validators;

	function __construct($root)
	{
		$this->root = is_array($root) ? $root : [];
		$this->validators = [];
		$this->path = [];
	}

	function __get($name)
	{
		$this->path[] = $name;
		return $this;
	}

	function __call($name, $args)
	{
		if (in_array($name, ['asInt', 'asNum', 'asStr', 'asArray'], true))
		{
			return $this->getAs($name, $args);
		}
		if (empty(self::$_validators[$name]))
		{
			self::$_validators[$name] = $this->getClosure('validators', $name, false);
		}
		$this->validators[$name] = $this->callClosure('validators', self::$_validators[$name], null, $args);
		return $this;
	}

	public function jsonSerialize()
	{
		return $this->root;
	}

	public function unwrap()
	{
		return $this->root;
	}

	private function getAs($type,$default)
	{
		$required = empty($default);
		list($isset, $value) = $this->get($required);
		if (!$isset)
		{
			if ($required){
				$this->validators = [];
				return null;
			}
			else
			{
				$value = $default[0];
			}
		}
		return $this->validate($value) ? $value: null;
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
					$this->emit('validation:failed', ['validator'=> 'required', 'input'=> implode('.', $this->path)]);
				}
				$this->path = [];
				return [false, null];
			}
		}
		$this->path = [];
		return [true, $cursor];
	}

	private function validate($value, $preserveRules=false)
	{
		foreach($this->validators as $name => $validator)
		{
			if(!$validator($value))
			{
				$preserveRules or $this->validators = [];
				$this->emit('validation:failed', ['validator'=> $name, 'input'=> $value]);
				return false;
			}
		}
		$preserveRules or $this->validators = [];
		return true;
	}

	public static function setValidators($validators)
	{
		self::$_validators = $validators;
	}

}
