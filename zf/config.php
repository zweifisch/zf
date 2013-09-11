<?php

use zf\Delayed;

return [
	'debug'       => false,
	'pretty'      => false,
	'dist'        => false,
	'extract'     => false,

	'charset'     => 'utf-8',
	'viewext'     => '.php',
	'views'       => 'views',
	'handlers'    => 'handlers',
	'helpers'     => 'helpers',
	'params'      => 'params',
	'mappers'     => 'mappers',
	'validators'  => 'validators',
	'view engine' => 'default',

	'components' => [
		'helper:zf\ClosureSet'          => [$this, Delayed::property($this->config, 'helpers')],
		'engines:zf\ClosureSet'         => [$this, Delayed::property($this->config, 'view engine'), require __DIR__ . DIRECTORY_SEPARATOR . 'engines.php'],
		'requestHandlers:zf\ClosureSet' => [$this, Delayed::property($this->config, 'handlers')],
		'paramHandlers:zf\ClosureSet'   => [$this, Delayed::property($this->config, 'params')],
		'validators:zf\ClosureSet'      => [$this, Delayed::property($this->config, 'validators'), require __DIR__ . DIRECTORY_SEPARATOR . 'validators.php'],
		'mappers:zf\ClosureSet'         => [$this, Delayed::property($this->config, 'mappers'), require __DIR__ . DIRECTORY_SEPARATOR . 'mappers.php'],
	],
];
