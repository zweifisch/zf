<?php

/*
 * php defaults.php ls users --limit=50 --skip=100 '*admin*'
 * php defaults.php ls users '*admin*' --skip=100 
 * php defaults.php ls users '*admin*'
 *
 */

require '../vendor/autoload.php';
$app = new \zf\App();

$app->param('skip', 'int');
$app->param('limit', 'int');

$app->cmd('ls users <pattern>', function(){
	$this->send($this->params);
})->options(['limit' => 20, 'skip' => 0]);

$app->run();
