<?php

// php demo-cli.php time Y-m-d
// php demo-cli.php ls users --female-only --skip 100 --limit 50 '*admin*'
// php demo-cli.php ls goods --female-only --skip 100 --limit 50 '*apple*'
// php demo-cli.php cat < ~/.bashrc
// php demo-cli.php forever

require '../zf/zf.php';

$app = new \zf\App();

$app->register('helper','\zf\Helper');

$app->helper->register('getTime', function($format){
	return date($format ,($_SERVER['REQUEST_TIME']));
});

$app->cmd('time <format>', function(){
	echo $this->helper->getTime($this->params->format);
});

$app->cmd('ls users <pattern>', ['--skip', '--limit', '--female-only'], function(){
	var_dump($this->params);
});

$app->cmd('ls goods <pattern>', ['--skip', '--limit', '--available-only'], function(){
	var_dump($this->params);
});

$app->cmd('cat', function(){
	echo $this->getstdin();
});

declare(ticks = 1);
$app->cmd('forever', function(){
	echo 'presss ctrl-c to quit', "\n";
	while(true)
	{
		echo 'running',"\n";
		sleep(1);
		if($this->stop) break;
	}
});

$app->sigint(function(){
	$this->stop = true;
	echo 'stopping';
});

$app->run();
