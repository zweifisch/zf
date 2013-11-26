<?php

use zf\Delayed;

return [
	'debug'       => false,
	'pretty'      => false,
	'dist'        => false,
	'extract'     => false,
	'mockup'      => false,

	'views'       => 'views',
	'handlers'    => 'handlers',
	'helpers'     => 'helpers',
	'middlewares' => 'middlewares',
	'params'      => 'params',
	'mockups'     => 'mockups',
	'mappers'     => 'mappers',
	'schemas'     => 'schemas',
	'validators'  => 'validators',

	'view engine' => 'default',
	'view extension' => '.php',

	'pathes' => [
		'views'       => 'views',
		'handlers'    => 'handlers',
		'helpers'     => 'helpers',
		'middlewares' => 'middlewares',
		'params'      => 'params',
		'mockups'     => 'mockups',
		'mappers'     => 'mappers',
		'schemas'     => 'schemas',
		'validators'  => 'validators',
	],

	'events' => [
		'response' => 'about to send the response',
		'exception' => 'an uncaught exception was thrown',
		'error' => 'error occoured',
		'shutdown' => 'shutting down',
		'validationfailed' => 'input validation failed',
	],

	'use middlewares' => ['response', 'json', 'inputParser'],

	'components' => [
		'helper:\zf\ClosureSet' => [
			'context' => $this,
			'path' => $this->config->delayed('helpers')
		],
		'engines:\zf\ClosureSet' => [
			'context' => $this,
			'path' => $this->config->delayed('view engine'),
			'closures' => require __DIR__ . DIRECTORY_SEPARATOR . 'engines.php'
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
		'validators:\zf\ClosureSet' => [
			'context' => $this,
			'path' => $this->config->delayed('validators'),
			'closures' => require __DIR__ . DIRECTORY_SEPARATOR . 'validators.php'
		],
		'mappers:\zf\ClosureSet' => [
			'context' => $this,
			'path' => $this->config->delayed('mappers'),
			'closures' => require __DIR__ . DIRECTORY_SEPARATOR . 'mappers.php'
		],
		'validator:\zf\Validator' => [
			'schemaPath' => $this->config->delayed('schemas')
		],
		'session:\zf\Session',
	],
];
