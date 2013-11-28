<?php

namespace zf\components;

use Exception;

class ViewEngine
{

	private $path;
	private $extension;
	private $context;

	public function __construct($path, $extension, $context)
	{
		$this->path = $path;
		$this->extension = $extension;
		$this->context = $context;
	}

	public function render($template, $vars, $context=null)
	{
		$context or $context = $this->context;
		$path = $this->path. DIRECTORY_SEPARATOR . $template . $this->extension;
		if(!stream_resolve_include_path($path)) throw new Exception("template $template($path) not found");

		$closure = function($_path, $_vars) {
			if($vars) extract($_vars);
			ob_start();
			include $_path;
			$ret = ob_get_contents();
			ob_end_clean();
			return $ret;
		};

		$closure = $closure->bindTo($context);
		return $closure($path, $vars);
	}
}
