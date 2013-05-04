<?php

// php -S localhost:5000
// curl -d name=zf -d passwd=secret -d _id=1 localhost:5000/user
// curl -d name=dos -d passwd=secret -d _id=2 localhost:5000/user
// curl -d name=dres -d passwd=secret -d _id=3 localhost:5000/user
// curl -i localhost:5000/users
// curl -i localhost:5000/users/1,3
// curl -i localhost:5000/users/1,2\?callback=my_cb
// curl -i -X DELETE localhost:5000/users/1,2
// curl -i localhost:5000/users
// curl -i -X DELETE localhost:5000/users/3
// curl -i localhost:5000/users
// curl -i localhost:5000/git/st
// curl localhost:5000/time/Y-m-d
// curl -i localhost:5000/

require '../zf/zf.php';

$app = new \zf\App();

$app->register('helper','\zf\Helper', $app);
$app->register('mongo','\zf\Mongo', $app->config->mongo);

$app->config('pretty', true);

$app->helper->register('getTime', function($format){
	return date($format ,($_SERVER['REQUEST_TIME']));
});

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
