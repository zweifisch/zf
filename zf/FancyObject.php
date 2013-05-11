<?php

namespace zf;

class FancyObject implements \JsonSerializable
{
	use Closure;
	use EventEmitter;
	private $root;
	private $path;
	private $usedValidators;
	private static $validators;
	private static $mappers;

	function __construct($root)
	{
		$this->root = is_array($root) ? $root : [];
		$this->usedValidators = [];
		$this->path = [];
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
		if (empty(self::$validators[$name]))
		{
			self::$validators[$name] = $this->getClosure('validators', $name, false);
		}
		$this->usedValidators[$name] = $this->callClosure('validators', self::$validators[$name], null, $args);
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
			if($required){
				$this->usedValidators = [];
				return null;
			}
			else
			{
				$value = $default[0];
			}
		}
		$value = $this->map($type, $value);
		return $this->validate($value) ? $value: null;
	}

	private function map($type, $value)
	{
		if(empty(self::$mappers[$type]))
		{
			self::$mappers[$type] = $this->getClosure('mappers', $type, false);
		}
		$mapper = self::$mappers[$type];
		return $mapper($value);
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
		foreach($this->usedValidators as $name => $validator)
		{
			if(!$validator($value))
			{
				$preserveRules or $this->usedValidators = [];
				$this->emit('validation:failed', ['validator'=> $name, 'input'=> $value]);
				return false;
			}
		}
		$preserveRules or $this->usedValidators = [];
		return true;
	}

	public static function setValidators($validators)
	{
		self::$validators = $validators;
	}

	public static function setMappers($mappers)
	{
		self::$mappers = $mappers;
	}

}
