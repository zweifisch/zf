<?php

use zf\Delayed;

return [
	'debug'       => false,
	'pretty'      => false,
	'dist'        => false,
	'extract'     => false,
	'mockup'      => false,

	'handlers'    => 'handlers',
	'helpers'     => 'helpers',
	'middlewares' => 'middlewares',
	'params'      => 'params',
	'mockups'     => 'mockups',
	'schemas'     => 'schemas',

	'pathes' => [
		'handlers'    => 'handlers',
		'helpers'     => 'helpers',
		'middlewares' => 'middlewares',
		'params'      => 'params',
		'mockups'     => 'mockups',
		'schemas'     => 'schemas',
	],

	'events' => [
		'response' => 'about to send the response',
		'exception' => 'an uncaught exception was thrown',
		'error' => 'error occoured',
		'shutdown' => 'shutting down',
		'validationfailed' => 'input validation failed',
	],

	'use middlewares' => ['response', 'json', 'bodyParser'],

	'components' => [
		'helper:\zf\ClosureSet' => [
			'context' => $this,
			'path' => $this->config->delayed('helpers')
		],
		'handlers:\zf\ClosureSet' => [
			'context' => $this,
			'path' => $this->config->delayed('handlers')
		],
		'middlewares:\zf\ClosureSet' => [
			'context' => $this,
			'path' => $this->config->delayed('middlewares'),
			'closures' => require __DIR__ . DIRECTORY_SEPARATOR . 'middlewares.php'
		],
		'paramHandlers:\zf\ClosureSet' => [
			'context' => $this,
			'path' => $this->config->delayed('params')
		],
		'validator:\zf\Validator' => [
			'schemaPath' => $this->config->delayed('schemas')
		],
		'engine:\zf\components\ViewEngine' => [
			'path' => 'views',
			'extension' => '.php',
			'context' => $this,
		],
		'request:\zf\components\Request',
		'response:\zf\components\Response',
		'params:\zf\components\Params',
		'session:\zf\Session',
		'user:\zf\components\User',
		IS_CLI ? 'router:\zf\CliRouter' : 'router:\zf\Router',
	],
];
