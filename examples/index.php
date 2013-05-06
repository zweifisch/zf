<?php

// port=5000
// php -S localhost:$port
// curl -d name=zf -d passwd=secret -d _id=1 localhost:$port/user
// curl -d name=dos -d passwd=secret -d _id=2 localhost:$port/user
// curl -d name=dres -d passwd=secret -d _id=3 localhost:$port/user
// curl -i localhost:$port/users
// curl -i localhost:$port/users/1,3
// curl -i localhost:$port/users/1,2\?callback=my_cb
// curl -i -X DELETE localhost:$port/users/1,2
// curl -i localhost:$port/users
// curl -i -X DELETE localhost:$port/users/3
// curl -i localhost:$port/users
// curl -i localhost:$port/git/st
// curl localhost:$port/time/Y-m-d
// curl -i localhost:$port/

require '../zf/zf.php';

$app = new \zf\App();

$app->config('pretty', true);
$app->register('mongo','\zf\Mongo', $app->config->mongo);

$app->param('ids', function($ids){
	return explode(',', $ids);
});

$app->get('/users/:ids?', function(){
	$criteria = [];
	if (isset($this->params->ids)){
		$criteria = ['_id' => ['$in' => $this->params->ids]];
	}
	$users = $this->mongo->users->find($criteria);
	$this->jsonp(iterator_to_array($users));
});

$app->post('/user', function(){
	$this->mongo->users->save($this->requestBody);
	$this->send(['ok' => true]);
});

$app->delete('/users/:ids', function(){
	$this->mongo->users->remove(['_id' => ['$in' => $this->params->ids]]);
	$this->send(['ok' => true]);
});

$app->get('/time/:format', function(){
	$this->send($this->helper->getTime($this->params->format));
});

$app->get('/git/:cmd', 'git');

$app->get('/', function(){
	$this->render('index',['now'=> date('H:i:s')]);
});

$app->run();
