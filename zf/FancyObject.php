<?

namespace zf;

class FancyObject implements \JsonSerializable
{
	use Closure;
	use EventEmitter;
	private $root;
	private $cursor;
	private $validators;
	private static $_validators;

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
		isset(self::$_validators[$name]) or self::$_validators[$name] = $this->getClosure('validators', $name, false);
		$this->validators[$name] = $this->callClosure('validators', self::$_validators[$name], null, $args);
		return $this;
	}

	function asInt($default=null)
	{
		$value = intval($this->get($default));
		return $this->validate($value) ? $value: null;
	}

	function asNum($default=null)
	{
		$value = floatval($this->get($default));
		return $this->validate($value) ? $value: null;
	}

	function asArray($default=null)
	{
		$value = $this->get($default);
		$value = is_array($value) ? $value: [];
		return $this->validate($value) ? $value: null;
	}

	function asStr($default=null)
	{
		$value = $this->get($default);
		return $this->validate($value) ? $value: null;
	}

	public function jsonSerialize()
	{
		return $this->root;
	}

	function unwrap()
	{
		return $this->root;
	}

	private function get($default)
	{
		$ret = is_null($this->cursor) ? $default : $this->cursor;
		$this->cursor = $this->root;
		return $ret;
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

	public static function setValidators($validators) {
		self::$_validators = $validators;
	}

}
