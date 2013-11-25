<?php

use zf\Delayed;

return [

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

	'roles' => [
		'Admin', 'Writer',
	],

	'permissions' => ['ReadPost', 'WritePost', 'DeletePost'],

	'components' => [
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
		],
	],
];
