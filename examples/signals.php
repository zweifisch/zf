<?php

/*
 * run php signals.php forever
 * pid will be print out
 * kill -USR1 $pid to reset
 * press ctrl-c or kill -INT $pid to quit
 *
 */

require '../vendor/autoload.php';

$app = new \zf\App();

declare(ticks = 1);
$app->cmd('forever', function(){
	$this->log(posix_getpid());
	$this->log('presss ctrl-c to quit');
	$this->done = false;
	$this->counter = 0;
	while(!$this->done){
		$this->log('%s secs elapsed', ++$this->counter);
		sleep(1);
	}
	$this->log('done');
});

$app->sigint(function(){
	$this->log('stopping');
	$this->done = true;
});

$app->sigusr1(function(){
	$this->counter = 0;
	$this->log('reset');
});

$app->run();
