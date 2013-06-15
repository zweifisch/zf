<?php

namespace zf;

class PDO extends \PDO
{
	private $_queries;
	private $_querie;
	private $_params;

	public function __construct($config)
	{
		parent::__construct($config['dsn'], $config['username'], $config['password']);
		if(isset($config['queries']))
		{
			$this->_queries = $config['queries'];
		}
	}

	public function fetchAll($fn=null)
	{
		return $this->execute(function($statement) use ($fn){
			return $fn
				? $statement->fetchAll(\PDO::FETCH_FUNC, $fn)
				: $statement->fetchAll(\PDO::FETCH_ASSOC);
		});
	}

	public function fetchOne($fn=null)
	{
		return $this->execute(function($statement) use ($fn){
			return $fn
				? $statement->fetch(\PDO::FETCH_FUNC, $fn)
				: $statement->fetch(\PDO::FETCH_ASSOC);
		});
	}

	public function fetchColumn($column=null)
	{
		return $this->execute(function($statement) use ($column){
			return $statement->fetchColumn((int)$column);
		});
	}

	public function update()
	{
		return $this->execute(function($statement){
			return $statement->rowCount();
		});
	}

	public function bind($params)
	{
		foreach($params as $name=>$value)
		{
			$this->_params[] = [$name,$value,$this->getType($value)];
		}
		return $this;
	}

	private function getType($val)
	{
		if(is_int($val)) return \PDO::PARAM_INT;
		if(is_bool($val)) return \PDO::PARAM_BOOL;
		if(is_null($val)) return \PDO::PARAM_NULL;
		return \PDO::PARAM_STR;
	}

	public function getLastError()
	{
		return $this->_statement ? $this->_statement->errorInfo() : null;
	}

	public function dumpParams()
	{
		$this->_statement->debugDumpParams();
	}

	public function __call($name,$args)
	{
		if(!strncmp('bind', $name, 4))
		{
			$type = '\PDO::PARAM_'.strtoupper(substr($name,4));
			if(!defined($type))
			{
				throw new \Exception("type \"$type\" not defined");
			}
			$this->_params[] = [$args[0], $args[1], constant($type)];
			return $this;
		}
	}

	public function __get($queryname)
	{
		if(empty($this->_queries[$queryname]))
		{
			throw new \Exception("query \"$queryname\" not defined");
		}
		$this->_query = $this->_queries[$queryname];
		return $this;
	}

	private function execute($fetch)
	{
		$this->_statement = $this->prepare($this->_query);
		if($this->_params){
			foreach($this->_params as $param)
			{
				list($name, $value, $type) = $param;
				$this->_statement->bindValue(':'.$name, $value, $type);
			}
			$this->_params = null;
		}
		$this->_statement->execute();
		$result = $fetch($this->_statement);
		$this->_query = null;
		$this->_statement->closeCursor();
		return $result;
	}
}
