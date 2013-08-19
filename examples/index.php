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
// curl -i $host:$port/time/Y-m-d
// curl -i $host:$port/
// curl -iH "Content-Type: application/json" -d '{"a":{"b":"c"}}' $host:$port/dump
// curl -X PUT -d a=b -d b\[\]=c $host:$port/dump
// curl -d a=b $host:$port/dump
// curl $host:$port/search\?keyword=nil\&page=1
// curl $host:$port/search\?keyword=nil\&page=1\&size=100
// curl -i $host:$port/search\?query=nil
// curl -H "Content-Type: application/json" -d '{"thing":{"key":"value"}}' $host:$port/thing
// curl -H "Content-Type: application/json" -d '{"thin":{"key":"value"}}' $host:$port/thing
// curl -I $host:$port/cache-control
// curl -i -d a=b $host:$port/debug
// curl -is -d a=b $host:$port/debug | sed -n '/X-ZF-Debug/ s/.* // p' | json

require '../zf/zf.php';

$app = new \zf\App();

$app->set('pretty');
$app->register('mongo','\zf\Mongo');

$app->param('ids', function($ids){
	if($ids) return explode(',', $ids);
});

$app->get('/users/:ids?', function(){
	$this->set('jsonp', 'callback');
	$criteria = [];
	if ($this->params->ids){
		$criteria = ['_id' => ['$in' => $this->params->ids]];
	}
	$users = $this->mongo->users->find($criteria);
	return iterator_to_array($users);
});

$app->post('/user', function(){
	$this->mongo->users->save($this->body->asArray());
	return ['ok' => true];
});

$app->delete('/users/:ids', function(){
	$this->mongo->users->remove(['_id' => ['$in' => $this->params->ids]]);
	return ['ok' => true];
});

$app->get('/time/:format', function(){
	return $this->helper->getTime($this->params->format);
});

$app->get('/git/:cmd', 'git');

$app->get('/', function(){
	$this->log('%s %s', $this->clientIP(), date('H:i:s'));
	return $this->render('index',['now'=> date('H:i:s')]);
});

$app->handler('dump', function(){
	return $this->body;
});

$app->post('/dump', function(){
	return $this->pass('dump');
});

$app->put('/dump', function(){
	return $this->pass('dump');
});

$app->get('/search', function(){
	$keyword = $this->query->keyword->asStr();
	$page = $this->query->page->min(1)->asInt();
	$size = $this->query->size->between(5,20)->asInt(10);
	return ['query'=>$this->query, 'result'=> []];
});

$app->on(zf\EVENT_VALIDATION_ERROR, function($message){
	$this->status(400);
	$this->end($message);
});

class Thing
{
	private $value;
	function __construct($value) {
		$this->value = $value;
	}

	function mutate()
	{
		return array_combine(array_values($this->value), array_keys($this->value));
	}
}

$app->map('Thing', function($value){
	return new Thing($value);
});

$app->post('/thing', function(){
	return $this->body->thing->asThing()->mutate();
});

$app->head('/cache-control', function(){
	$this->cacheControl('public', ['max-age'=>120]);
});

$app->post('/debug', function(){
	$this->set('debug');
	$this->debug('input', $this->body);
	$this->debug('ip', $this->clientIP());
	return 200;
});

$app->run();
