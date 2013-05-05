<?php

namespace zf;

trait EventEmitter
{
	private $listeners;

	public function on($event, $callback)
	{
		$this->listeners[$event][] = $callback;
	}

	public function emit($event, $data)
	{
		if(is_array($this->listeners[$event]))
		{
			foreach($this->listeners[$event] as $callback)
			{
				if($this->callClosure('events', $callback, $this, [$data])) break;
			}
		}
	}
}
