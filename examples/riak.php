<?php

require '../vendor/autoload.php';
$app = new zf\App;

$app->cmd('set <key> <value>', function($key, $value) {
	$this->riak->bucket->$key = $value;
});

$app->cmd('get <key>', function($key) {
	return $this->riak->bucket->$key->getData();
});

$app->cmd('set2 <key> <value>', function($key, $value) {
	$this->riak['bucket'][$key] = $value;
});

$app->cmd('get2 <key>', function($key) {
	return $this->riak['bucket'][$key]->getData();
});

$app->cmd('set3', function() {
	$this->riak['bucket']['key'] = ['k'=>date('Y-m-d H:i:s')];
	return $this->riak['bucket']['key']->getData();
});

$app->cmd('get3', function() {
	return [$this->riak['bucket9']['key']->exists()];
});

$app->cmd('get3', function() {
	return [isset($this->riak['bucket9']['key'])];
});

$app->cmd('keys', function() {
	return $this->riak->bucket9->getKeys();
});

$app->cmd('keys2', function() {
	$this->riak['bucket9']['key']->getData();
	$this->riak['bucket9']['key2'] = null;
	return $this->riak->bucket9->getKeys();
});

$app->cmd('keys3', function() {
	unset($this->riak['bucket9']['key2']); // aync ?
	return $this->riak->bucket9->getKeys();
});

$app->run();
