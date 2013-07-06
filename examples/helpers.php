<?php

require '../vendor/autoload.php';
$app = new \zf\App();

$app->helper('getTimestamp', function(){
	return $_SERVER['REQUEST_TIME'];
});

$app->helper(['item']);

$app->cmd('time <format>', function(){
	echo $this->helper->getTime($this->params->format);  // loaded from helpers/getTime.php
});

$app->cmd('timestamp', function(){
	$this->log($this->helper->getTimestamp());
	// registered using $app->helper(), `helper` can be ommited
	$this->log($this->getTimestamp());
});

$app->cmd('item', function(){
	$this->log($this->helper->item('key', [], 'default'));
	// `helper` can be ommited
	$this->log($this->item('key', ['key'=>'value'], 'default'));
});

$app->run();
