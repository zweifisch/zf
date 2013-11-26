<?php

use zf\Delayed;

return [

	'roles' => [
		'Admin', 'Writer',
	],
	'permissions' => ['ReadPost', 'WritePost', 'DeletePost'],

	'components' => [
		'redis:zf\Redis' => [
			'config' => [
				'default' => [
					'host'     => 'localhost',
					'pconnect' => true,
				]
			]
		],
		'mongo:zf\Mongo' => [
			'config' => [
				'users','posts','files:GridFS' => [
					'url'        => 'mongodb://localhost:27017',
					'database'   => 'project',
					// 'options'    => ['readPreference' => MongoClient::RP_SECONDARY_PREFERRED],
				]
			]
		],
		'db:zf\PDO' => [
			'config' => [
				'dsn' => 'mysql:host=localhost;dbname=mysql;charset=utf8',
				'username' => 'root',
				'password' => 'secret',
				'queries' => [
					'users' => [
						'list' =>
						'select User,Host from user limit :skip,:limit',
					]
				],
			]
		],
		'sessionStore:\zf\components\RedisSessionHandler',
		'session:zf\Session',
		'user:zf\User' => [
			'secret' => 'secret'
		]
	],
];
