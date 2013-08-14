<?php

require '../vendor/autoload.php';

$app = new zf\App();

$app->set('view engine', 'mustache');

$app->get('/', function(){
	return $this->render('index', [
		'now' => date('Y-m-d H:i:s'),
		'engine' => 'mustache',
		'upper' => $this->helper->upper,
	]);
});

$app->run();
