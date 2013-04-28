# zf

a micro php framework/router

* routeing to closure or class method(*scalability*)
* `param` handler (inspired by expressjs)
* jsonp support
* commandline routing
* can be used with or without composer
* ideal for building apis and commandline apps

## synopsis

if you're not using composer, you need to import it
```php
require 'zf/zf.php';
```

install using [composer](http://getcomposer.org/)
```javascript
{
  "require": {
    "zweifisch/zf": "*"
  }
}
```


```php
$app = new \zf\App();

$app->get('/hello/:name', function(){
	$this->send($this->params->name);
})

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

```php
$app->register('mongo', '\zf\Mongo', $app->config->mongo);
$app->register('mongo', '\zf\Redis', $app->config->redis);

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
	$users = [2=>'zf'];
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
	if(count($ids) > 0){
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
```php
$app->sigint(function(){
	echo 'ctrl-z pressed';
	exit(0);
});
```

## scalability

routing to a class
```php
$app
	->get('/user/:id', ['\controllers\User', 'get'])
	->post('/user', ['\controllers\User', 'create'])
	->param('id', ['\handlers\Param', 'ensureInt'])
```

the same using a namespace prefix
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
* mongo
```sh
sudo pecl install mongo
```

