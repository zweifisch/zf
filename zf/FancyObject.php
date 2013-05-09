<?

namespace zf;

class FancyObject
{
	private $path;
	private $data;
	private $validated = false;

	function __construct($data)
	{
		$this->data = $data;
	}

	function __get($name)
	{
		if ($this->validated)
		{
			$this->path = [];
		}
		$this->path[] = $name;
		return $this;
	}

	function __call($name,$args)
	{
		$default = isset($args[0])? $args[0] : null;
		$this->path[] = $name;
		return $this->get($default);
	}

	private function get($default)
	{
		$ret = $this->data;
		foreach($this->path as $seg)
		{
			if (!isset($ret[$seg]))
			{
				$ret = $default;
				break;
			}
			$ret = $ret[$seg];
		}
		$this->path = [];
		return $ret;
	}

	function asInt()
	{
		return intval($this->get(0));
	}

	function asNum()
	{
		return floatval($this->get(0));
	}

	function asArray()
	{
		$ret = $this->get();
		return is_array($ret) ? $ret : [];
	}

}
