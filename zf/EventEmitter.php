<?php

namespace zf;

trait EventEmitter
{
	private $listeners;
	private $eventsIndex;
	private $parent;

	public function on($event, $callback)
	{
		3 == func_num_args()
			? list($event, $priority, $callback) = func_get_args()
			: $priority = 0;

		$keys = explode(':', $event);

		foreach($keys as $key)
		{
			if($key == '*') continue;
			isset($this->eventsIndex[$key]) or $this->eventsIndex[$key] = [];
			in_array($event, $this->eventsIndex[$key], true) or $this->eventsIndex[$key][] = $event;
		}

		$this->listeners[$event][] = [$priority, $callback];

		return $this->emit('listener:registered',['event'=>$event, 'callback'=>$callback]);
	}

	private function getMatchedEvents($event)
	{
		$keys = explode(':', $event);
		$numSegments = count($keys);
		if(1 == $numSegments) return [$event];

		$ret = [];
		foreach($keys as $key)
		{
			if(empty($this->eventsIndex[$key])) continue;

			foreach($this->eventsIndex[$key] as $e)
			{
				if(in_array($e, $ret, true)) continue;

				$segments = explode(':', $e);
				if(count($segments) != $numSegments) continue;

				$match = true;
				foreach($segments as $idx=>$segment)
				{
					if($segment != '*' && $segment != $keys[$idx])
					{
						$match = false;
						break;
					}
				}
				if($match) $ret[] = $e;
			}
		}
		return $ret;
	}

	private function getListeners($event)
	{
		$events = $this->getMatchedEvents($event);
		if(empty($events)) return [];

		$ret = [];
		foreach($events as $e)
		{
			$ret = array_merge($ret, $this->listeners[$e]);
		}
		uasort($ret, function($a, $b){ return $a[0] > $b[0] ? -1 : 1; });
		return array_map(function($listener){ return $listener[1];}, $ret);
	}

	public function emit($event, $data=null)
	{
		foreach($this->getListeners($event) as $listener)
		{
			$listener = $listener->bindTo($this);
			if($listener($data, $event)) return $this;
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
