<?php

require '../vendor/autoload.php';

$app = new \zf\App();
$app->set('pretty');

$app->cmd('exception', function(){
	throw new \Exception("exception!!");
});

$app->cmd('error', function(){
	$this->log($msg);
});

$app->on('exception', function($exception){
	$this->log($exception->getMessage());
});

$app->on('shutdown', function(){
	$this->log('shutting down');
});

$app->on('error', function($error){
	$this->log($error->str);
	die();
});

$app->run();
