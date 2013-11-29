<?php

return [
	'schema' => function($schema) {
		if($this->errors = $this->validator->validate($this->body, $schema)) {
			$this->emit('validationfailed', ['errors'=> $this->errors]);
		}
	},
	'mockup' => function($mockup) {
		if($this->config->mockup) {
			$path = $this->resolvePath($this->config->mockups, explode('/', $mockup));
			if(!is_readable($path))
				throw new Exception("failed to load mockup from '$path'");
			$content = file_get_contents($path);
			if('.json' == substr($mockup, -5)) {
				return json_decode($content);
			} elseif ('.yaml' == substr($mockup, -5)) {
				return yaml_parse($content);
			}
			return $content;
		}
	},
	'jsonp' => function($callback='callback') {
		if(!empty($_GET[$callback])) {
			return function($response) use ($callback) {
				$callback = $_GET[$callback];
				$response->body("$callback && $callback({$response->body})", 'text/javascript');
			};
		}
	},
	'json' => function($pretty='false',$encoding='utf-8') {
		return function($response) use ($pretty, $encoding) {
			if(!is_string($response->body)) {
				$response->body('true' === $pretty ? json_encode($response->body, JSON_PRETTY_PRINT) : json_encode($response->body), 'application/json', $encoding);
			}
		};
	},
	'response' => function($charset='utf-8'){
		return function($response){
			$response->send();
		};
	},
	'debug' => function($header='X-Debug'){

		$this->helper('debug', function($msg, $object){
			if($this->config->debug){
				list($bt) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,1);
				$this->response->debug[] = [
					$msg,
					$object,
					basename($bt['file']),
					$bt['line'],
				];
			}
			return $this;
		});

		return function() use ($header) {
			if(is_array($this->response->debug)){
				$this->response->header($header, json_encode($this->response->debug));
			}
		};
	},
	'phar' => function(){
		if(!$this->request->isCli) return;
		if('.phar' == substr($_SERVER['SCRIPT_FILENAME'], -5) && $this->config->extract)
		{
			$this->cmd('extract <path>', function(){
				try {
					$phar = new \Phar($_SERVER['SCRIPT_FILENAME']);
					$phar->extractTo($this->params->path, null, true);
				} catch (Exception $e) {
					echo $e->getMessage();
					exit(1);
				}
			});
		}
		elseif($this->config->dist)
		{
			$this->cmd('dist <name>', function(){
				$entryScript = basename($_SERVER['SCRIPT_FILENAME']);
				$phar = new \Phar($this->params->name, FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME, $this->params->name);
				$phar->buildFromDirectory($this->config->basedir, '/\.php$/');
				$phar->setStub($phar->createDefaultStub($entryScript));
			});
		}
	},
	'bodyParser' => function() {
		$this->body = function() {
			if ('GET' == $this->request->method)
			{
				$ret = null;
			}
			elseif ($this->request->contentTypeMatches('application/json', 16))
			{
				$ret = json_decode($this->request->body);
			}
			elseif ($this->request->contentTypeMatches('application/x-www-form-urlencoded', 33))
			{
				'POST' == $this->request->method ? $ret = $_POST : parse_str($this->request->body, $ret);
			}
			elseif ($this->request->contentTypeMatches('multipart/form-data', 19))
			{
				$ret = array_merge($_POST, $_FILES);
			}
			else
			{
				$ret = $this->request->body;
			}
			return $ret;
		};
	},
	'msgpack' => function($header = 'application/x-msgpack'){
		$this->body = function() {
			if ($this->request->contentTypeMatches($header))
			{
				return msgpack_unpack($this->request->body);
			}
		};
	},
];
