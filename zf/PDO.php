<?php

namespace zf;

class PDO extends \PDO
{
	public function __construct($config)
	{
		parent::__construct($config['dsn'], $config['username'], $config['password']);
	}

	public function fetchAll($query, $parameters=[], $fn=null)
	{
		$statement = $this->execute($query, $parameters);
		$ret = $fn
			? $statement->fetchAll(\PDO::FETCH_FUNC, $fn)
			: $statement->fetchAll(\PDO::FETCH_ASSOC);
		$statement->closeCursor();
		return $ret;
	}

	public function fetchOne($query, $parameters=[], $fn=null)
	{
		$statement = $this->execute($query, $parameters);
		$ret = $fn
			? $statement->fetch(\PDO::FETCH_FUNC, $fn)
			: $statement->fetch(\PDO::FETCH_ASSOC);
		$statement->closeCursor();
		return $ret;
	}

	public function fetchColumn($query, $parameters=[], $column=0)
	{
		$statement = $this->execute($query, $parameters);
		$ret = $statement->fetchColumn($column);
		$statement->closeCursor();
		return $ret;
	}

	public function fetch($query, $params=[])
	{
		$statement= $this->prepare($query);
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		$statement->execute($params);
		return $statement;
	}

	public function update($query, $parameters)
	{
		$statement = $this->execute($query, $parameters);
		return $statement->rowCount();
	}

	private function execute($query, $parameters=[])
	{
		$statement = $this->prepare($query);
		$statement->execute($parameters);
		return $statement;
	}
}
