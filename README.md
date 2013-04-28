# zf

a micro php framework/router

* both closure and class method can be used as request handler(easy to scale)
* `param` handler (inspired by expressjs)
* jsonp support
* commandline routing
* can be used with or without composer
* ideal for building apis and commandline apps

## synopsis

to install using [composer](http://getcomposer.org/), add the fellowing to your `composer.json`
```javascript
{
  "require": {
    "zf/zf": "*"
  }
}
```

if you're not using composer, download soruce code [here](https://github.com/zweifisch/zf/tags)

```php
require 'vendor/autoload.php'; #  require 'zf/zf.php'; if you are not using composer

$app = new \zf\App();

$app->get('/hello/:name', function(){
	$this->send($this->params->name);
});

$app->run();
```

### chaining

```php

$app
	->get('/user/:id', ['User', 'get'])
	->post('/user', ['User', 'create'])
	->run();
```

### component

components are just classes attached to the $app instance, any class can used, `\zf\Mongo`, `\zf\Redis` and `\zf\Helper` are available out of the box:
```php
$app->register('mongo', '\zf\Mongo', $app->config->mongo);
$app->register('redis', '\zf\Redis', $app->config->redis);

// \zf\Mongo won't be initilazed unless $app->mongo is accessed
$app->mongo->users->findOne();
```

### helper

```php
$app->register('helper', '\zf\Helper');
$app->helper->register('item', function($array, $key, $default=null){
	return isset($array[$key]) ? $array[$key] : $default;
});

$app->get('/user/:id', function(){
	$users = [2 => 'zf'];
	$this->send($this->helper->item($users, $this->params->id, 'not found'));
});
```

### param

```php
$app->delete('/users/:user_ids', function() {
	var_dump(is_array($this->params->user_ids));
});

$app->param('user_ids', function($user_ids) {
	$ids = explode('-', $user_ids);
	if(count($ids) <= 10){
		return $ids;
	}
	$this->send(401);
});
```

### jsonp

```php
$app->jsonp($result);
```

if `$_GET['callback']` is set js will returned, otherwise it's equivelent to `$this->send($result)`

## cli

```php
$app->cmd('hello <name>', function(){
	echo 'say hello to ', $this->params->name;
});

$app->cmd('ls user <pattern>', ['--skip', '--limit', '--female-only'], function(){
	var_dump($this->params);
});
```

### handle signals

see `examples/cli.php` for more details
```php
$app->sigint(function(){
	echo 'ctrl-c pressed';
	exit(0);
});
```

## scalability

using class as request handler
```php
$app
	->get('/user/:id', ['\controllers\User', 'get'])
	->post('/user', ['\controllers\User', 'create'])
	->param('id', ['\handlers\Param', 'ensureInt'])
```

using a namespace prefix
```php
$app->ns('\controllers')
	->get('/user/:id', ['User', 'get'])
	->post('/user', ['User', 'create'])
	->ns('\handlers')
	->param('id', ['Param', 'ensureInt'])
```

```php
namespace controllers;
class User
{
	function get($params, $app){ }
	
	function create(){ }
}
```

you have to make `\controllers\Users` and `\handlers\Param` reachable
and they won't be loaded and initialized unless necessary, (zf is *lazy*)

### helpers

```php
$app->register('helper', '\zf\Helper');

$app->helper->register(require 'helpers.php');
$app->helper->register(require 'more-helpers.php');
```

`helpers.php`
```php
return [
	'item' => function(){ ... }
	'eleemnt' => function(){ ... }
];
```

## optional dependencies

* [phpredis](https://github.com/nicolasff/phpredis)
* mongo `sudo pecl install mongo`

## examples

there is an exmaple demostrating how to add/list/delte users, to run it using php's builtin server: (needs the php mongo extension metioned above)
```sh
cd examples && php -S localhost:5000
```
a cli example is also included.
