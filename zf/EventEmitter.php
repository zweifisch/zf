<?php

namespace zf;

trait EventEmitter
{
	private $listeners;
	private $eventsIndex;
	private $parent;
	private $lastHandler;

	public function on($event, $callback, $once=false)
	{
		$priority = 0;
		$keys = explode(':', $event);

		foreach($keys as $key)
		{
			if($key == '*') continue;
			isset($this->eventsIndex[$key]) or $this->eventsIndex[$key] = [];
			in_array($event, $this->eventsIndex[$key], true) or $this->eventsIndex[$key][] = $event;
		}

		$handler = [$priority, $callback, $once];
		$this->lastHandler = &$handler;
		$this->listeners[$event][] = &$handler;

		$this->emit('listener:registered',['event'=>$event, 'callback'=>$callback]);
		return $this;
	}

	public function once($event, $callback)
	{
		return $this->on($event, $callback, true);
	}

	public function priority($priority)
	{
		$this->lastHandler[0] = $priority;
		return $this;
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
			if(isset($this->listeners[$e]))
			{
				$ret = array_merge($ret, $this->listeners[$e]);
				$this->listeners[$e] = array_filter($this->listeners[$e], function($listener){
					return !$listener[2]; // not 'once'
				});
			}
		}
		uasort($ret, function($a, $b){ return $a[0] > $b[0] ? -1 : 1; });
		return array_map(function($listener){ return $listener[1];}, $ret);
	}

	public function emit($event, $data=null)
	{
		$listeners = $this->getListeners($event);
		foreach($listeners as $listener)
		{
			$listener = $listener->bindTo($this);
			if($listener($data, $event)) return true;
		}
		return is_null($this->parent) ? (bool)$listeners : $this->parent->emit($event, $data);
	}

	public function setParent($parent)
	{
		$this->parent = $parent;
		return $this;
	}
}
