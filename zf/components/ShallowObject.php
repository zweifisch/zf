<?php

namespace zf\components;

use JsonSerializable;

class ShallowObject implements JsonSerializable
{

    protected $_source;

    public function __construct($source)
    {
        $this->_source = $source;
    }

    public function __get($key)
    {
        return isset($this->_source[$key]) ? $this->_source[$key] : null;
    }

    public function __set($key, $val)
    {
        $this->_source[$key] = $val;
    }

    public function jsonSerialize()
    {
        return $this->_source;
    }

    public function __isset($key)
    {
        return isset($this->_source[$key]);
    }
}
