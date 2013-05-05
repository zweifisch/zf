<?php

$exports = [];

$exports['mongo'] = [
	'users' => [
		'url'        => 'mongodb://localhost:27017',
		'database'   => 'project',
		'collection' => 'users',
		'options'    => ['readPreference' => MongoClient::RP_SECONDARY_PREFERRED],
	]];

$exports['redis'] = [
	'default' => [
		'host'     => 'localhost',
		'pconnect' => true,
	]];

return $exports;
