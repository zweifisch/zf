<?php

namespace zf;

class Validator
{
	private $validator;
	private $schemaFolder;
	private $schemas;

	public function __construct($schemaPath)
	{
		$this->validator = new \jsonschema\Validator;
		$this->schemaFolder = $schemaPath;
		$this->schemas = [];
	}

	public function validate($input, $schema)
	{
		return $this->validator->validate($input, $this->getSchema($schema));
	}

	private function getSchema($name)
	{
		if(!isset($this->schemas[$name]))
		{
			$this->schemas[$name] = json_decode(file_get_contents($this->schemaFolder.DIRECTORY_SEPARATOR.$name));
		}
		return $this->schemas[$name];
	}
}
