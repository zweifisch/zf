<?php

$exports = [];

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

$exports['roles'] = [
	'Admin', 'Writer',
];

$exports['permissions'] = ['ReadPost', 'WritePost', 'DeletePost'];

$exports['components'] = [
	'redis:zf\Redis' => [
		'default' => [
			'host'     => 'localhost',
			'pconnect' => true,
		]
	],
	'mongo:zf\Mongo' => [
		'users','posts','files:GridFS' => [
			'url'        => 'mongodb://localhost:27017',
			'database'   => 'project',
			// 'options'    => ['readPreference' => MongoClient::RP_SECONDARY_PREFERRED],
		]
	]
];

return $exports;
