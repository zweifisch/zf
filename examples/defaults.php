<?php

/*
 * php defaults.php ls users --limit 50 --skip 100 '*admin*'
 * php defaults.php ls users '*admin*' --skip 100 
 * php defaults.php ls users '*admin*'
 *
 */

require '../vendor/autoload.php';
$app = new \zf\App();

$app->param('max', 'int');
$app->param('from', 'int');

$app->cmd('ls users --skip <from> --limit <max> <pattern>', function(){
	$this->send($this->params);
})->defaults(['max' => 20]);

$app->run();
