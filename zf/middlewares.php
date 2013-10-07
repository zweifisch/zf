<?php

return [
	'body' => function($schema){
		if($errors = $this->validator->validate($this->body->asRaw(), $schema)){
			$this->emit('validationfailed', ['errors'=> $errors]);
			$this->errors = $errors;
			$this->body = null;
		}else{
			$this->body = $this->body->asRaw();
		}
	},
	'mockup' => function($mockup){
		if($this->config->mockup)
		{
			$path = $this->resolvePath($this->config->mockups, explode('/', $mockup));
			if(!is_readable($path))
			{
				throw new Exception("failed to load mockup from '$path'");
			}
			$content = file_get_contents($path);
			if('.json' == substr($mockup, -5))
			{
				return json_decode($content);
			}
			return $content;
		}
	},
];
