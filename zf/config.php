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
		'helper:\zf\ClosureSet'          => [$this, Delayed::property($this->config, 'helpers')],
		'engines:\zf\ClosureSet'         => [$this, Delayed::property($this->config, 'view engine'), require __DIR__ . DIRECTORY_SEPARATOR . 'engines.php'],
		'handlers:\zf\ClosureSet'        => [$this, Delayed::property($this->config, 'handlers')],
		'middlewares:\zf\ClosureSet'     => [$this, Delayed::property($this->config, 'middlewares'), require __DIR__ . DIRECTORY_SEPARATOR . 'middlewares.php'],
		'paramHandlers:\zf\ClosureSet'   => [$this, Delayed::property($this->config, 'params')],
		'validators:\zf\ClosureSet'      => [$this, Delayed::property($this->config, 'validators'), require __DIR__ . DIRECTORY_SEPARATOR . 'validators.php'],
		'mappers:\zf\ClosureSet'         => [$this, Delayed::property($this->config, 'mappers'), require __DIR__ . DIRECTORY_SEPARATOR . 'mappers.php'],
		'validator:\zf\Validator'        => [Delayed::property($this->config, 'schemas')],
		'session:\zf\Session',
	],
];
