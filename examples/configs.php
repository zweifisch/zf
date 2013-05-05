<?php

$exports = [];

$exports['mongo'] = [
	'users' => [
		'url'        => 'mongodb://localhost:27017',
		'database'   => 'project',
		'collection' => 'users',
	]];

$exports['redis'] = [
	'default' => [
		'host'     => 'localhost',
		'pconnect' => true,
	]];

return $exports;
