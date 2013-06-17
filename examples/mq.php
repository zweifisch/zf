<?php

require '../zf/zf.php';

$app = new \zf\App();

$app->set('pretty');
$app->register('mq','\zf\AMQP', $app->config->mq);

$app->cmd('send', function(){
	$this->mq->queue('queue1')->declear();
	$this->mq->fanoutExchange('exchange1')->publish(date('Y-m-d H:i:s'), 'key1');
});

$app->cmd('receive', function(){
	while($envelope = $this->mq->queue('queue1')->bind('exchange1','key1')->get(AMQP_AUTOACK))
	{
		echo ($envelope->isRedelivery()) ? 'Redelivery' : 'New Message', PHP_EOL;
		echo $envelope->getBody(), PHP_EOL;
	}
});

$app->run();
