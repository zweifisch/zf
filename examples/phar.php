<?php

/*
 * php phar.php dist phar.phar
 * php phar.phar extract .
 *
 */

require '../zf/zf.php';
$app = new \zf\App();

$app->set(['dist', 'extract']);

$app->cmd('pid', function(){
	$this->log(posix_getpid());
});

$app->run();
