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
	'jsonp' => function($callback='callback'){
		if(!empty($_GET[$callback])){
			return function($response) use ($callback) {
				$callback = $_GET[$callback];
				$response['body'] = "$callback && $callback({$response['body']})";
				$response['type'] = 'text/javascript';
			};
		}
	},
	'json' => function($pretty='false',$encoding='utf-8'){
		return function(&$response) use ($pretty, $encoding) {
			if(!is_string($response['body']) && empty($response['type'])){
				$response['body'] = 'true' === $pretty ? json_encode($response['body'], JSON_PRETTY_PRINT) : json_encode($response['body']);
				$response['type'] = 'application/json';
				$response['charset'] = $encoding;
			}
		};
	},
	'response' => function($charset='utf-8'){
		return function($response){
			$this->send($response);
		};
	},
	'debug' => function($header='X-Debug'){

		$this->helper('debug', function($msg, $object){
			if($this->config->debug){
				list($bt) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,1);
				$this->debug[] = [
					$msg,
					$object,
					basename($bt['file']),
					$bt['line'],
				];
			}
			return $this;
		});

		return function() use ($header) {
			if(is_array($this->debug)){
				$this->header($header, json_encode($this->debug));
			}
		};
	},
	'phar' => function(){
		if(!$this->isCli) return;
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
	'inputParser' => function(){
		$this->query = function(){
			return (new \zf\FancyObject($_GET, $this->validators, $this->mappers))->setParent($this);
		};
		if ('GET' == $this->requestMethod) return;

		$this->body = function(){
			$contentType = isset($_SERVER['HTTP_CONTENT_TYPE']) ? $_SERVER['HTTP_CONTENT_TYPE'] : '';
			$ret = '';
			if (!strncmp($contentType,'application/json', 16))
			{
				$ret = json_decode(file_get_contents('php://input'));
			}
			elseif ($contentType == 'application/x-www-form-urlencoded')
			{
				'POST' == $this->requestMethod ? $ret = $_POST : parse_str(file_get_contents('php://input'), $ret);
			}
			elseif (!strncmp($contentType, 'multipart/form-data', 19))
			{
				$ret = array_merge($_POST, $_FILES);
			}
			else
			{
				$ret = file_get_contents('php://input');
			}
			return (new \zf\FancyObject($ret, $this->validators, $this->mappers))->setParent($this);
		};
	},
	'msgpackParser' => function($header = 'application/x-msgpack'){
		$this->body = function() {
			$contentType = isset($_SERVER['HTTP_CONTENT_TYPE']) ? $_SERVER['HTTP_CONTENT_TYPE'] : '';
			if ($contentType == $header)
			{
				return msgpack_unpack(file_get_contents('php://input'));
			}
		};
	},
];
