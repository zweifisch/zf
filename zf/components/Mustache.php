<?php

namespace zf\components;

class Mustache
{

	private $path;
	private $extension;

	public function __construct($path, $extension='mustache')
	{
		$this->path = $path;
		$this->extension = $extension;
	}

	public function render($template, $vars, $context=null)
	{
		$mustache = new Mustache_Engine([
			'loader' => new Mustache_Loader_FilesystemLoader($this->path),
		]);
		return $mustache->render($template, $vars);
	}
}
