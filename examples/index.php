<?php

// port=5000
// host=localhost
// php -S localhost:$port
// curl -d name=zf -d passwd=secret -d _id=1 $host:$port/user
// curl -d name=dos -d passwd=secret -d _id=2 $host:$port/user
// curl -d name=dres -d passwd=secret -d _id=3 $host:$port/user
// curl -i $host:$port/users
// curl -i $host:$port/users/1,3
// curl -i $host:$port/users/1,2\?callback=my_cb
// curl -i -X DELETE $host:$port/users/1,2
// curl -i $host:$port/users
// curl -i -X DELETE $host:$port/users/3
// curl -i $host:$port/users
// curl -i $host:$port/git/st
// curl $host:$port/time/Y-m-d
// curl -i $host:$port/
// cat >! request.json << EOF
// {"a":{"b":"c"}}
// EOF
// curl -H "Content-Type: application/json" -d @request.json $host:$port/dump
// curl -X PUT -d a=b $host:$port/dump
// curl -d a=b $host:$port/dump

require '../zf/zf.php';

$app = new \zf\App();

$app->config('pretty');
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
	$this->mongo->users->save($this->body->unwrap());
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

$app->post('/dump', function(){
	$this->send($this->body->unwrap());
});

$app->put('/dump', function(){
	$this->send($this->body->unwrap());
});

$app->run();
