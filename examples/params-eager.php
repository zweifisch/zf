<?php

/*
 * php params-eager.php params 1 2 3
 *
 */

require '../vendor/autoload.php';
$app = new \zf\App();

$show = function($value){
	$this->log('param processed: %s', $value);
	return $value;
};

$app->param('param1', $show);
$app->param('param2', $show);
$app->param('param3', $show)->eager(); # eager to get processed

$app->cmd('params <param1> <param2> <param3>', function(){
	$this->log('params2: %s', $this->params->param2);
});

$app->run();
