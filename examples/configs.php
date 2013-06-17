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

return $exports;
