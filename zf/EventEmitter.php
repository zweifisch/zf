<?php

namespace zf;

trait EventEmitter
{
	private $listeners;
	private $parent;

	public function on($event, $callback)
	{
		$this->listeners[$event][] = $callback;
		return $this;
	}

	public function emit($event, $data)
	{
		if(isset($this->listeners[$event]) && is_array($this->listeners[$event]))
		{
			foreach($this->listeners[$event] as $callback)
			{
				if($this->callClosure('events', $callback, $this, [$data])) return $this;
			}
		}
		is_null($this->parent) or $this->parent->emit($event, $data);
		return $this;
	}

	public function setParent($parent)
	{
		$this->parent = $parent;
		return $this;
	}
}
