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

	'events' => [
		'response' => 'about to send the response',
		'exception' => 'an uncaught exception was thrown',
		'error' => 'error occoured',
		'shutdown' => 'shutting down',
		'validationfailed' => 'input validation failed',
	],

	'use middlewares' => ['response', 'json', 'bodyParser'],

	'components' => [
		'helper:ClosureSet' => [
			'context' => $this,
			'path' => $this->config->delayed('helpers')
		],
		'handlers:ClosureSet' => [
			'context' => $this,
			'path' => $this->config->delayed('handlers')
		],
		'middlewares:ClosureSet' => [
			'context' => $this,
			'path' => $this->config->delayed('middlewares'),
			'closures' => require __DIR__ . DIRECTORY_SEPARATOR . 'middlewares.php'
		],
		'paramHandlers:ClosureSet' => [
			'context' => $this,
			'path' => $this->config->delayed('params')
		],
		'validator:Validator' => [
			'schemaPath' => $this->config->delayed('schemas')
		],
		'engine:ViewEngine' => [
			'path' => 'views',
			'extension' => '.php',
			'context' => $this,
		],
		'request:Request',
		'response:Response',
		'params:Params',
		'session:Session',
		'user:User',
		IS_CLI ? 'router:CliRouter' : 'router:Router',
	],
];
