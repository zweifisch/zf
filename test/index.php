<?php

require '../vendor/autoload.php';

$app = new \zf\App();

$app->useMiddleware('debug');

$app->param('ids', function($ids){
	if($ids) return explode(',', $ids);
});

$app->get('/users/:ids?', function($ids = null) {
	$criteria = $ids ? ['_id' => ['$in' => $ids]] : [];
	$users = $this->mongo->users->find($criteria);
	return array_values(iterator_to_array($users));
});

$app->post('/user', function(){
	$this->mongo->users->save($this->body);
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
	$this->trace($this->request->ip .' '. date('H:i:s'));
	return $this->response->render('index', ['now'=> date('H:i:s')], $this);
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

$app->onValidationFailed(function($message){
	$this->response->status = 400;
	$this->response->body = json_encode($message);
	$this->response->send();
});

$app->head('/cache-control', function(){
	$this->response->cacheControl('public', ['max-age'=>120]);
});

$app->post('/debug', function(){
	$this->set('debug');
	$this->debug('input', $this->body);
	$this->debug('ip', $this->request->ip);
	return '';
});

$app->get('/foo/:bar/:opt?', function($bar, $opt='', $q='', $offset=0, $limit=10){
	return [
		'compact' => compact('bar', 'opt', 'q', 'offset', 'limit'),
		'params' => $this->params,
	];
});

$app->get('/bar/:foo?', function($q, $foo=''){
	return [
		'compact' => compact('q', 'foo'),
		'params' => $this->params,
	];
});

$app->get('/foo', function(){
	return $this->params;
});

$app->middleware('auth', function($user,$passwd){
	if($this->get('PHP_AUTH_USER') != $user || $this->get('PHP_AUTH_PW') != $passwd){
		$this->header('WWW-Authenticate', ['Basic realm'=> 'Login Required']);
		$this->status(401);
		return 'Unauthorized';
	}
});

$app->get('/admin', 'auth:admin,secret', function(){
	return 'admin';
});

$app->get('/console', 'console');

$app->resource('posts');

$app->get('/status', function() {
	return 404;
});

$app->get('/path', function() {
	return $this->request->path;
});

$app->get('/buffer', function() {
	echo $this->request->ip;
	$this->trace($this->request->ip);
	die();
	return 'echo to log';
});

$app->any('/any', function() {
	return $this->request->method;
});

$app->run();
