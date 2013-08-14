<?php

return [

	'default' => function($template, $vars){
		$renderWithContext = function($template, $closure){
			$path = $this->config->views . DIRECTORY_SEPARATOR . $template . $this->config->viewext;
			if(!stream_resolve_include_path($path)) throw new \Exception("template $template($path) not found");
			$closure = $closure->bindTo($this);
			return $closure($path);
		};
		return $renderWithContext($template, function($path) use ($vars){
			if($vars) extract($vars);
			ob_start();
			include $path;
			$ret = ob_get_contents();
			ob_end_clean();
			return $ret;
		});
	},

	'mustache' => function($template, $vars){
		$mustache = new Mustache_Engine([
			'loader' => new Mustache_Loader_FilesystemLoader($this->config->views),
		]);
		return $mustache->render($template, $vars);
	}
];
