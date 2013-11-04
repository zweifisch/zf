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

$app->run();
