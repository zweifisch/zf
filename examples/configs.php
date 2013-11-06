<?php

return [

	'mongo' => [
		'users' => [
			'url'        => 'mongodb://localhost:27017',
			'database'   => 'project',
			// 'options'    => ['readPreference' => MongoClient::RP_SECONDARY_PREFERRED],
		]
	],

	'mq' => [
		'host'     => 'localhost',
		'login'    => 'guest',
		'password' => 'guest',
	],

	'db' => [
		'dsn' => 'mysql:host=localhost;dbname=mysql;charset=utf8',
		'username' => 'root',
		'password' => 'secret',
		'queries' => [
			'users' => [
				'list' =>
					'select User,Host from user limit :skip,:limit',
			]
		],
	],

	'components' => [
		'riak:\zf\components\Riak' => [
			'host' => '127.0.0.1',
			'port' => 8098,
		],
		'redis:\zf\Redis' => [
			'default' => [
				'host'     => 'localhost',
				'pconnect' => true,
			]
		],
	],

];
