<?php

require '../vendor/autoload.php';

$app = new zf\App();
$app->set('pretty');

$app->cmd('exception', function(){
	throw new \Exception("exception!!");
});

$app->cmd('error', function(){
	$this->log($msg);
});

$app->on(zf\EVENT_EXCEPTION, function($exception){
	$this->log($exception->getMessage());
});

$app->on(zf\EVENT_SHUTDOWN, function(){
	$this->log("\nshutting down");
});

$app->on(zf\EVENT_ERROR, function($error){
	$this->log($error->str);
	die();
});

$app->run();
