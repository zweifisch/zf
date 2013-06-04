# zf [![Build Status](https://travis-ci.org/zweifisch/zf.png?branch=master)](https://travis-ci.org/zweifisch/zf)

a micro php framework/router for both web and cli

* use closure as request handler
* `param` handler(inspired by expressjs)
* commandline routing
* events
* jsonp support
* lazy
* scalable
* requires php 5.4
* ideal for building restful apis or commandline apps

## synopsis

to install using [composer](http://getcomposer.org/), add the fellowing to your `composer.json`
```javascript
{
  "require": {
    "zweifisch/zf": "*"
  }
}
```

if you're not using composer, download soruce code [here](https://github.com/zweifisch/zf/tags)

```php
require 'vendor/autoload.php'; #  require 'zf/zf.php'; if you are not using composer

$app = new \zf\App();

$app->get('/hello/:name', function(){
	$this->send(['hello' => $this->params->name]);
})->run();
```

### component

components are just classes attached to the $app instance, any class can be used, `\zf\Mongo` and `\zf\Redis` are available out of the box:
```php
$app->register('mongo', '\zf\Mongo', $app->config->mongo);
$app->register('redis', '\zf\Redis', $app->config->redis);

// \zf\Mongo won't be initilazed unless $app->mongo is accessed
$app->mongo->users->findOne();
```

### param

```php
$app->delete('/users/:user_ids', function() {
	$this->send(['deleted'=>count($this->params->user_ids)]);
});

$app->param('user_ids', function($user_ids) {
	$ids = explode('-', $user_ids);
	if(count($ids) <= 10){
		return $ids;
	}
	$this->send(400);
});
```
the param handler won't be called, unless `$this->params->user_ids` is accessed

### events

```php
$app->on('user:hit', function($data){
	# write to log
});

$app->on('user:hit', function($data){
	$this->redis->users->zincrby('hotusers',1,$data['_id']);
});

$app->get('/user/:id', function($data){
	$user = $this->mongo->user->findOne(['_id'=>$this->params->id]);
	$this->emit('user:hit', $user);
	$this->send($user);
});
```

handler with highest priority get called first, the default priority is `0`
```php
$app->on('event', -1, function(){
	// will be called after all handlers
});
```

listening for multiple events
```php
$app->on('user:*', function($data, $event){
	// will get called for `user:login`, `user:logout` etc.
});
```

to stop an event, return a truthy value in the handler

### helper

```php
$app->helper('item', function($array, $key, $default=null){
	return isset($array[$key]) ? $array[$key] : $default;
});

$app->get('/user/:id', function(){
	$users = [2 => 'zf'];
	$this->send($this->helper->item($users, $this->params->id, 'not found'));
});
```

registrated helpers can also be accessed using `$this->myhelper();`

### call other request handler

```php
$app->handler('index', function(){
	$this->render('index');
});

$app->handler('/index.php', function(){
	$this->pass('index');
});
```

### access inputs

#### request body

`$this->body` is parsed from raw request body according to content type (json|formdata)
and wraped as a `FancyObject`, `$app->set('nofancy');` will keep it as an array.

request body:
```json
{
	"action": "login",
	"user": {
		"name" : "admin",
		"password": "secret"
	}
}
```

access them:
```php
$action = $this->body->action->in('login','register')->asStr();
$name = $this->body->user->name->minlen(3)->maxlen(20)->asStr();
$password = $this->body->user->password->minlen(8)->asStr();
```

#### query string

access `$_GET['page']` and `$_GET['size']`
```php
$page = $this->query->page->asInt(1);
$size = $this->query->size->between(10,20)->asInt();
```
#### asX

available types are `asStr`, `asInt`, `asNum`, `asArray`, `asFile`
```php
$app->post('/upload',function(){
	$file = $this->body->image->asFile();
	$file->extension;
	new \MongoBinData($file->content); //  content won't be read unless accessed
});
```

it's possible to add new types:
```php
$app->map('User', function($value){
	return new User($value);
});

$this->body->asUser()->save();
```

### validation

when validation fails, `null` will be returned and `validation:failed` will be emmitted.
```php
$password = $this->body->user->password->minlen(8)->asStr();
// all keys are required, unless a default value is supplied:
$gender = $this->body->user->gender->in(0,1)->asInt(0);

$app->on('validation:failed', function($message){
	$this->send(400, $message);
});
```

available validators `between`, `min`, `max`, `in`, `minlen`, `maxlen`, `match`

add a new validator:
```php
$app->validator('startWith', function($str) {
	return function($value) use ($str) {
		return 0 == strncmp($value, $value, strlen($str));
	};
});

// use it
$this->body->some->key->startWith(':')->asStr();
```

### response

```php
$this->send($statusCode);
$this->send($statusCode, $body);
$this->send($statusCode, $body, ['type'=>$contentType]);
$this->send($body);
$this->send($body, ['type'=>$contentType, 'charset'=>$charset]);
```

json response
```php
$this->set('pretty'); #  enable json pretty print
$this->send($object);
```

#### lastModified

```php
$this->lastModified($timestamp);
```

#### cacheControl

```php
$this->cacheControl('public', 'must-revalidate', ['max-age'=> 60]);
```

### jsonp

```php
$app->jsonp($result);
```

if `$_GET['callback']` is set, javascript will be returned, otherwise it's equivelent to `$this->send($result)`

### views

there is no nested, complicated server side view rendering mechanism in `zf`.
but it's still possible to rendering simple views in plain old php. please
consider client side view rendering using requirejs with knockoutjs, angularjs
or similar libs/framworks.

in request handler
```php
$this->render('index',['pageview'=>1000]);
```

and in `views/index.php`
```php
<!DOCTYPE HTML>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title></title>
</head>
<body>
	Pageview today: <?= $this->pageview ?> 
</body>
</html>
```

to specify a different location other than `views`, use `$app->set('views','path/to/templates');`

### configs

`configs.php` will be loaded if exists

set
```php
$app->set('key','value');

$app->set('fancy');  # equivelant to $app->set('fancy', true);
$app->set('nofancy');  # equivelant to $app->set('fancy', false);

$app->set(['pretty', 'nofancy', 'key'=>'value']); # multiple set
```

retrieve 
```php
$app->config->key;
```

### chaining

```php
$app->get('/user/:id', function(){
	# ...
})->post('/user', function(){
	# ...
})->run();
```

### debug

dump object in header

```php
$app->set('debug');
$app->debug($msg, $object);
```

## cli

```php
$app->cmd('hello <name>', function(){
	echo 'say hello to ', $this->params->name;
});

$app->set('pretty');

$app->cmd('ls user --skip <from> --limit <max> <pattern>', function(){
	$this->send($this->params);
});
```

all options are required, unless default values are provided
```php
$app->cmd('ls user --skip <from> --limit <max> <pattern>', function(){
	# ...
})->defaults(['max' => 20]);
```

### help info

if no command matched(404), a help message will be printed, and program will exit with err(code 1);

### get piped input

use `$this->getstdin();`

### handle signals

see `examples/cli.php` for more details
```php
$app->sigint(function(){
	echo 'ctrl-c pressed';
	exit(0);
});
```

## scalability

### request/param/event handlers, validators and mappers

all can be put in it's own file
```php
$app->post('/user', 'create-user');
```
return a closure in `handlers/create-user.php`
```php
return function() {
	# ...
};
```
request handlers should be located in `handlers` by default, this can be changed using `$app->set('handlers','path/to/handlers');`

similarly, event handlers in `events`, param handlers in `params` ...

### helpers

when calling a helper which is not registrated, `zf` will look for it under `helpers`
```php
$app->on('error', function($data){
	# helpers/mail.php will be loaded
	$this->helper->mail(['to'=>$this->config->admin,'body'=>$data->message]);
});
```

helpers can also be registrated in this way:
```php
$app->helper(['helper','helper2','helper3']);

// so they can be accessed as
$app->helper3(); #  ommit the 'helper'
```

## laziness

* `$app->register` won't initilize class
* `$app->attr = closure` closure won't be invoked unless `$app->attr` is accessed
* param handler won't be called unless `$app->params->param` is accessed, to make the handler get called as soon as possible, supply a extra parameter like this `$app->param('param','handler',true);`
* request body won't be parsed unless `$app->body` is accessed

## optional dependencies

* [phpredis](https://github.com/nicolasff/phpredis)
* mongo `sudo pecl install mongo`

## examples

there is an exmaple demostrating how to add/list/delte users, to run it using php's builtin server: (needs the php mongo extension metioned above)
```sh
cd examples && php -S localhost:5000
```
a cli example is also included.

## tests

run tests
```sh
composer.phar install --dev
vendor/bin/phpunit -c tests
```
