<?php

require '../vendor/autoload.php';

$app = new zf\App();

$app->useMiddleware('debug');

$app->param('ids', function($ids){
	if($ids) return explode(',', $ids);
});

/**
 * @param string $ids
 */
$app->get('/users/:ids?', function($ids=null) {
	$criteria = $ids ? ['_id' => ['$in' => $ids]] : [];
	$users = $this->mongo->users->find($criteria);
	return array_values(iterator_to_array($users));
});

$app->post('/user', function() {
	$this->mongo->users->save($this->body);
	return ['ok' => true];
});

/**
 * @param string $ids
 */
$app->delete('/users/:ids', function($ids) {
	$this->mongo->users->remove(['_id' => ['$in' => $ids]]);
	return ['ok' => true];
});

/**
 * @param string $format
 */
$app->get('/time/:format', function($format) {
	return $this->helper->getTime($format);
});

$app->get('/', function($request, $response) {
	$this->trace($request->ip .' '. date('H:i:s'));
	return $response->render('index', ['now'=> date('H:i:s')], $this);
});

$app->any('/dump', function($body) {
	return $body;
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

/**
 * @param string $bar
 * @param string $opt
 * @param string $q
 * @param string $offset
 * @param string $limit
 */
$app->get('/foo/:bar/:opt?', function($bar, $opt='', $q='', $offset=0, $limit=10){
	return [
		'compact' => compact('bar', 'opt', 'q', 'offset', 'limit'),
		'params' => $this->params,
	];
});

/**
 * @param string $q
 * @param string $foo
 */
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
		return [401, 'Unauthorized', 'WWW-Authenticate'=>
			['Basic realm'=> 'Login Required']
		];
	}
});

/**
 * @auth admin,secret
 */
$app->get('/admin', function(){
	return 'admin';
});

$app->resource('post');

$app->get('/status', function() {
	return 404;
});

$app->get('/status-and-body', function() {
	return [201, 'created', 'X-RESOURCE-ID' => 99];
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

$app->get('/redirect', function() {
	$this->response->redirect('/redirected');
});

$app->get('/redirected', function() {
	return 'redirected';
});

$app->run();
