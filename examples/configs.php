<?php

$exports = [];

$exports['mongo'] = [
	'users' => [
		'url'        => 'mongodb://localhost:27017',
		'database'   => 'project',
		'options'    => ['readPreference' => MongoClient::RP_SECONDARY_PREFERRED],
	]
];

$exports['redis'] = [
	'default' => [
		'host'     => 'localhost',
		'pconnect' => true,
	]
];

$exports['mq'] = [
	'host'     => 'localhost',
	'login'    => 'guest',
	'password' => 'guest',
];

$exports['db'] = [
	'dsn' => 'mysql:host=localhost;dbname=mysql;charset=utf8',
	'username' => 'root',
	'password' => 'secret',
	'queries' => [
		'users' => [
			'list' =>
				'select User,Host from user limit :skip,:limit',
		]
	],
];

return $exports;
