<?php

return array(
	'mongo' => array(
		'users' => array(
			'url'        => 'mongodb://localhost:27017',
			'database'   => 'project',
			'collection' => 'users',
		),
	),
	'redis' => array(
		'default' => array(
			'host'     => 'localhost',
			'pconnect' => true,
		),
	),
);
