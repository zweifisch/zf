<?php

namespace zf\components;

use MongoClient;

class MongoGridFS
{
	public function __construct($url, $options=[], $database=null)
	{
		$this->_url = $url;
		$this->_options = $options;
		$this->_db = $database;
		$client = new MongoClient($this->_url, $this->_options);
		return $client->selectDB($database)->getGridFS();
	}

}
