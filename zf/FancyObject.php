<?php

namespace zf;

use JsonSerializable;

class FancyObject implements JsonSerializable
{
	use EventEmitter;
	private $_root;
	private $_path;
	private $_validators;

	function __construct($root, $validators, $mappers)
	{
		$this->_root = is_array($root) ? $root : [];
		$this->_validators = [];
		$this->_path = [];
		$this->validatorSet = $validators;
		$this->mapperSet = $mappers;
	}

	function __get($name)
	{
		$this->_path[] = $name;
		return $this;
	}

	function __call($name, $args)
	{
		if (0 == strncmp('as', $name, 2))
		{
			return $this->_getAs(substr($name, 2), $args);
		}
		$this->_validators[$name] = $this->validatorSet->__call($name, $args);
		return $this;
	}

	public function extract($keys)
	{
		$path = $this->_path;
		$ret = [];
		foreach($keys as $key => $validators)
		{
			$default = null;
			if(is_int($key)) // no validation
			{
				$key = $validators;
			}
			else
			{
				foreach(explode('|', $validators) as $validator)
				{
					list($name, $args) = explode(':', $validator);
					$args = explode(',', $args);
					$name == 'default'
						? $default = $args
						: $this->_validators[$name] = $this->validatorSet->__call($name, $args);
				}
			}
			$exploded = explode(':', $key);
			2 == count($exploded) ? list($key, $type) = $exploded : $type = 'Str';
			$this->_path = $path ? array_merge($path, explode('.', $key)) : explode('.', $key);
			if(is_null($ret[$key] = $this->_getAs($type, $default)))
			{
				return null;
			}
		}
		return $ret;
	}

	public function jsonSerialize()
	{
		return $this->_root;
	}

	private function _getAs($type, $default)
	{
		$required = empty($default);
		list($isset, $value) = $this->_get($required);
		if(!$isset)
		{
			if($required)
			{
				return $this->_done(null);
			}
			else
			{
				$value = $default[0];
			}
		}
		elseif(is_string($value))
		{
			$value = trim($value);
		}
		$mappedValue = $this->mapperSet->__call($type, [$value, implode('.', $this->_path)]);
		if(is_null($mappedValue))
		{
			$this->emit(EVENT_VALIDATION_ERROR, ['validator'=> $type, 'input'=> $value, 'key'=> implode('.', $this->_path)]);
			return $this->_done(null);
		}
		return $this->_validate($mappedValue) ? $this->_done($mappedValue): $this->_done(null);
	}

	private function _done($ret)
	{
		$this->_path = [];
		$this->_validators = [];
		return $ret;
	}

	private function _get($required)
	{
		$cursor = $this->_root;
		foreach ($this->_path as $path)
		{
			if(isset($cursor[$path]))
			{
				$cursor = $cursor[$path];
			}
			else
			{
				if ($required)
				{
					$this->emit(EVENT_VALIDATION_ERROR, ['validator'=> 'required', 'input'=>null, 'key'=> implode('.', $this->_path)]);
				}
				$this->_path = [];
				return [false, null];
			}
		}
		return [true, $cursor];
	}

	private function _validate($value, $preserveRules=false)
	{
		foreach($this->_validators as $name => $validator)
		{
			if(!$validator($value))
			{
				$preserveRules or $this->_validators = [];
				$this->emit(EVENT_VALIDATION_ERROR, ['validator'=> $name, 'input'=> $value, 'key'=> implode('.', $this->_path)]);
				return false;
			}
		}
		$preserveRules or $this->_validators = [];
		return true;
	}

}
