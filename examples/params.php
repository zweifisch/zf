<?php

/*
 * run php params.php list 1 2
 * only accessed params get proccessed
 *
 */

require '../vendor/autoload.php';
$app = new \zf\App();

$app->param('skip', function($value){
	$this->log('param `skip` processed');
	return (int)$value;
});

$app->param('limit', function($value){
	$this->log('param `limit` processed');
	return $value;
});

$app->cmd('list <limit> <skip>', function(){
	$this->log('skip: %s %s', $this->params->skip, gettype($this->params->skip));
});

$app->run();
