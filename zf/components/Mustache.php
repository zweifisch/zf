<?php

namespace zf\components;

use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

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
