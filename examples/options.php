<?php

/*
 * php options.php mv foo bar
 * php options.php mv --overwrite foo bar
 *
 */

require '../vendor/autoload.php';
$app = new \zf\App();

$app->param('skip', 'int');
$app->param('limit', 'int');

$app->set('pretty');

$app->cmd('mv <src> <target>', function(){
	return $this->params;
})->options(['overwrite','interactive']);

$app->cmd('mail <to>', function(){
	return $this->params;
})->options(['cc'=>'','subject'=>'untitled']);

$app->run();
