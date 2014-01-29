<?php

use zf\Delayed;

return [
	'debug'       => false,
	'pretty'      => false,
	'dist'        => false,
	'extract'     => false,
	'mockup'      => false,

	'events' => [
		'response' => 'about to send the response',
		'exception' => 'an uncaught exception was thrown',
		'error' => 'error occoured',
		'shutdown' => 'shutting down',
		'validationfailed' => 'input validation failed',
	],

	'use' => ['response', 'json', 'status', 'bodyParser'],

	'components' => [
		'helper' => 'ClosureSet', [
			'context' => $this,
			'path' => 'helpers'
		],
		'handlers' => 'ClosureSet', [
			'context' => $this,
			'path' => 'handlers',
		],
		'middlewares' => 'ClosureSet', [
			'context' => $this,
			'path' => 'middlewares',
			'closures' => require __DIR__ . DIRECTORY_SEPARATOR . 'middlewares.php'
		],
		'paramHandlers' => 'ClosureSet', [
			'context' => $this,
			'path' => 'params',
		],
		'resource' => 'Resource', [
			'path' => 'resources',
			'namespace' => 'resources',
		],
		'validator' => 'Validator', [
			'schemaPath' => 'schemas',
		],
		'engine' => 'ViewEngine' , [
			'path' => 'views',
			'extension' => '.php',
			'context' => $this,
		],
		'request' => 'Request',
		'response' => 'Response',
		'params' => 'Params',
		'session' => 'Session',
		'cookie' => 'Cookie',
		'router' => IS_CLI ? 'CliRouter' : 'WebRouter',
	],
];
