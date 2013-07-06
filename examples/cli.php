<?php

/*
 * php cli.php cat < ~/.bashrc
 * php cli.php git st
 *
 */

require '../vendor/autoload.php';
$app = new \zf\App();

$app->cmd('cat', function(){
	echo $this->getstdin();
});

$app->cmd('git <cmd>', 'git'); // load from handlers/git.php

$app->run();
