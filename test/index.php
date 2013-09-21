<?php

require '../vendor/autoload.php';

$app = new \zf\App();

$app->set('pretty');

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
	return array_values(iterator_to_array($users));
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
	return new Thing(get_object_vars($value));
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

$app->get('/foo/:bar/:opt?', function($bar, $opt='', $q='', $offset=0, $limit=10){
	return [
		'compact' => compact('bar', 'opt', 'q', 'offset', 'limit'),
		'params' => $this->params,
		'query' => $this->query,
	];
});

$app->get('/bar/:foo?', function($q, $foo=''){
	return [
		'compact' => compact('q', 'foo'),
		'params' => $this->params,
		'query' => $this->query,
	];
});

$app->get('/foo', function(){
	$keyword = $this->query->keyword->asStr();
	$page = $this->query->page->min(1)->asInt();
	$size = $this->query->size->between(5,20)->asInt(10);
	return compact('keyword', 'page', 'size');
});

$app->resource('posts', ['comments']);

$app->run();
