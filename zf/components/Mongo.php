<?php

namespace zf\components;

use Exception, ArrayAccess, MongoId, MongoClient;
use zf\Data;

class Mongo implements ArrayAccess
{

	private $_config;
	private $_cachedConnections = [];
	private $_currentCollection;

	public function __construct($collections)
	{
		$this->_config = $collections;
	}

	public function __get($collection)
	{
		if(empty($this->_config[$collection]))
		{
			$this->_config = Data::pushRight($this->_config);
			if(empty($this->_config[$collection]))
			{
				throw new Exception("collection \"$collection\" not defined");
			}
		}

		$config = $this->_config[$collection];
		if(empty($this->_cachedConnections[$config['url']]))
		{
			$this->_cachedConnections[$collection] = isset($config['options'])
				? new MongoClient($config['url'], $config['options'])
				: new MongoClient($config['url']);
		}
		$db = $this->_cachedConnections[$collection]->selectDB($config['database']);
		$this->_currentCollection = $db->selectCollection($collection);
		return $this;
	}

	public function __call($name, $params)
	{
		if ($this->_currentCollection)
		{
			$currentCollection = $this->_currentCollection;
			$this->_currentCollection = null;
			return call_user_func_array([$currentCollection, $name], $params);
		}
		throw new Exception("collection not specified when calling \"$name\"");
	}

	public function findOneById($id, $projection=[])
	{
		$result = $this->__call('findOne', [['_id' => new MongoId($id)], $projection]);
		if (isset($result['_id']) && $result['_id'] instanceof MongoId)
		{
			$result['_id'] = (string)$result['_id'];
		}
		return $result;
	}

	public function updateById($id, $doc)
	{
		return $this->__call('update', [['_id' => new MongoId($id)], $doc]);
	}

	public function set($id, $doc)
	{
		return $this->__call('update', [['_id' => new MongoId($id)], ['$set' => $doc]]);
	}

	public function insert($doc)
	{
		$ret = $this->__call('insert', [$doc]);
		$ret['_id'] = is_array($doc) ? (string)$doc['_id'] : (string)$doc->_id;
		return $ret;
	}

	public function getMongoId()
	{
		return new MongoId;
	}

	public function offsetGet($offset)
	{
		return $this->__get($offset);
	}

	public function offsetExists($offset) { }
	public function offsetSet($offset, $value) { }
	public function offsetUnset($offset) { }

}
