<?php

namespace zf;

use Exception;

class Config
{
	private $_configs = [];
	private $_context;

	public function __construct($context=null)
	{
		$this->_context = $context;
	}

	public function __get($key)
	{
		if(array_key_exists($key, $this->_configs))
		{
			return $this->_configs[$key];
		}
		throw new Exception("config key '$key' not found");
	}

	public function __set($key, $value)
	{
		$this->_configs[$key] = $value;
	}

	public function get($key, $default=null)
	{
		return isset($this->_configs[$key]) ? $this->_configs[$key] : $default;
	}

	public function set($key, $value=null)
	{
		if(1 == func_num_args())
		{
			is_array($key) ? $this->mset($key) : $this->setBool($key);
		}
		else
		{
			$this->_configs[$key] = $value;
		}
	}

	public function update($configs)
	{
		if (isset($configs['components']))
		{
			$configs['components'] = array_map(function($value) {
				return [
					'class' => '\\' == $value[0]{0} ? $value[0] : '\\zf\\components\\' . $value[0],
					'constructArgs' => isset($value[1]) ? $value[1] : [],
				];
			}, Data::pushLeft($configs['components']));
		}
		if($this->_configs)
		{
			foreach ($configs as $key => $value)
			{
				if (is_array($value) && isset($this->_configs[$key]))
				{
					$this->_configs[$key] = $value + $this->_configs[$key];
				}
				else
				{
					$this->_configs[$key] = $value;
				}
			}
		}
		else
		{
			$this->_configs = $configs;
		}
	}

	public function load($path, $quiet=false)
	{
		$requireWithContext = function($path) {
			return require($path);
		};
		if($this->_context)
		{
			$requireWithContext = $requireWithContext->bindTo($this->_context);
		}
		if(stream_resolve_include_path($path))
		{
			$this->update($requireWithContext($path));
		}
		else
		{
			if(!$quiet) throw new Exception("config '$path' can't be loaded");
		}
	}

	private function mset($options)
	{
		foreach($options as $key=>$value)
		{
			is_int($key)? $this->setBool($value) : $this->_configs[$key] = $value;
		}
	}

	private function setBool($key)
	{
		strncmp('no', $key, 2)
			? $this->_configs[$key] = true
			: $this->_configs[substr($key, 2)] = false;
	}

	public function __isset($key)
	{
		return isset($this->_configs[$key]);
	}

	public function delayed($key)
	{
		return function() use ($key) {
			return $this->__get($key);
		};
	}
}
