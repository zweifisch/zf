<?

namespace zf;

trait Validater
{
	private $validaters;

	function minlen($len)
	{
		$this->validaters[] = function($value) use ($len) { return strlen($value) >= $len; };
		return $this;
	}

	function maxlen($len)
	{
		$this->validaters[] = function($value) use ($len) { return strlen($value) <= $len; };
		return $this;
	}

	function min($min)
	{
		$this->validaters[] = function($value) use ($len) { return $value >= $min; };
		return $this;
	}

	function max($max)
	{
		$this->validaters[] = function($value) use ($len) { return $value <= $max; };
		return $this;
	}

	function between($min,$max)
	{
		$this->validaters[] = function($value) use ($len) { return $value <= $max; };
		return $this;
	}

	function in($values)
	{
		is_array($values) or $values = func_get_args();
		$this->validaters[] = function($value) use ($values) { return in_array($value, $values, true); };
		return $this;
	}

	private function validate($value,$preserveRules=false)
	{
		foreach($this->validaters as $validater)
		{
			if(!$validater($value))
			{
				$preserveRules or $this->validaters = [];
				return false;
			}
		}
		$preserveRules or $this->validaters = [];
		return true;
	}
}

class FancyObject
{
	use Validater;
	private $root;
	private $cursor;

	function __construct($root)
	{
		$this->root = $root;
		$this->cursor = $this->root;
	}

	function __get($name)
	{
		$this->cursor = isset($this->cursor[$name]) ? $this->cursor[$name] : null;
		return $this;
	}

	function __call($name,$args)
	{
		$default = isset($args[0])? $args[0] : null;
		return $this->__get($name)->get($default);
	}

	function asInt()
	{
		$value = intval($this->get(0));
		return $this->validate($value) ? $value: null;
	}

	function asNum()
	{
		$value = floatval($this->get(0));
		return $this->validate($value) ? $value: null;
	}

	function asArray()
	{
		$value = $this->get();
		$value = is_array($value) ? $value: [];
		return $this->validate($value) ? $value: null;
	}

	function asStr()
	{
		$value = $this->get('');
		return $this->validate($value) ? $value: null;
	}

	private function get($default)
	{
		$ret = is_null($this->cursor) ? $default : $this->cursor;
		$this->cursor = $this->root;
		return $ret;
	}

	public function unwrap()
	{
		return $this->root;
	}
}
