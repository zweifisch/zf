<?php

/*
 * php redis.php incr counter --by=2
 * php redis.php incr counter
 *
 */

require '../vendor/autoload.php';
$app = new \zf\App();

$app->register('redis','\zf\Redis');

$app->cmd('incr <key>', function(){
	echo $this->redis->default->incr($this->params->key, $this->params->by);
})->options(['by'=>1]);

$app->run();
