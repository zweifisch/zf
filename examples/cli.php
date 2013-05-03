<?php

// php cli.php time Y-m-d
// php cli.php ls users --limit 50 --skip 100 '*admin*'
// php cli.php ls users '*admin*' --skip 100 
// php cli.php ls users '*admin*'
// php cli.php cat < ~/.bashrc
// php cli.php git ls
// php cli.php incr counter --by 2
// php cli.php incr counter
// php cli.php forever

require '../zf/zf.php';

$app = new \zf\App();

$app->register('helper','\zf\Helper', $app);
$app->register('redis','\zf\Redis', $app->config->redis);

$app->helper->register('getTime', function($format){
	return date($format ,($_SERVER['REQUEST_TIME']));
});

$app->cmd('time <format>', function(){
	echo $this->helper->getTime($this->params->format);
});

$app->cmd('ls users --skip <from> --limit <max> <pattern>', function(){
	var_dump($this->params);
})->defaults(['max' => 20]);

$app->cmd('cat', function(){
	echo $this->getstdin();
});

$app->cmd('git <cmd>', 'git');

$app->cmd('incr <key> --by <by>', function(){
	echo $this->redis->default->incr($this->params->key, $this->params->by);
})->defaults(['by'=>1]);

declare(ticks = 1);
$app->cmd('forever', function(){
	echo 'presss ctrl-c to quit', "\n";
	$this->done = false;
	while(true)
	{
		echo 'running',"\n";
		sleep(1);
		if($this->done) break;
	}
});

$app->sigint(function(){
	echo 'stopping';
	sleep(1);
	$this->done = true;
});

$app->run();
