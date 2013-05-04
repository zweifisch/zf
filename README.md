# zf

a micro php web/cli framework/router

* use closure as request handler
* `param` handler (inspired by expressjs)
* commandline routing
* jsonp support
* can be used with or without composer
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

components are just classes attached to the $app instance, any class can used, `\zf\Mongo`, `\zf\Redis` and `\zf\Helper` are available out of the box:
```php
$app->register('mongo', '\zf\Mongo', $app->config->mongo);
$app->register('redis', '\zf\Redis', $app->config->redis);

# \zf\Mongo won't be initilazed unless $app->mongo is accessed
$app->mongo->users->findOne();
```

### param

```php
$app->delete('/users/:user_ids', function() {
	$this->send(count($this->params->user_ids));
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

### access inputs

`$this->requestBody` is an array, parsed from raw request body according to content type (json|formdata)

`$this->getParam($key, $defaultValue)` gets value from requestBody

`$this->getQuery($key, $defaultValue)` gets value from `$_GET`

### response

```php
$this->send($statusCode);
$this->send($statusCode,$body);
$this->send($statusCode,$body,$contentType);
$this->send($body);
```

json response
```php
$this->config('pretty', true); #  enable json pretty print
$this->send($object);
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

to specify a different location other than `views`, use `$app->config('views','path/to/templates');`

### configs

`configs.php` will be loaded if exists

set
```php
$app->config('key','value');
```

retrieve 
```php
$app->config->key;
```

## cli

```php
$app->cmd('hello <name>', function(){
	echo 'say hello to ', $this->params->name;
});

$app->config->('pretty', true);

$app->cmd('ls user --skip <from> --limit <max> <pattern>', function(){
	this->send($this->params);
});
```

### chaining

```php
$app->get('/user/:id', function(){
	# ...
})->post('/user', function(){
	# ...
})->run();
```

all params are required, unless default values are provided
```php
$app->cmd('ls user --skip <from> --limit <max> <pattern>', function(){
	# ...
})->defaults(['max' => 20]);
```

### help info

if no command matched(404), a help message will be printed, and program will exit with err(code 1);

### get piped input

use `$this->getstdin`

### handle signals

see `examples/cli.php` for more details
```php
$app->sigint(function(){
	echo 'ctrl-c pressed';
	exit(0);
});
```

## scalability

single handler in it's own file
```php
$app->post('/user', 'create-user');
```
here is `handlers/create-user.php`
```php
return function() {
	# ...
};
```

request handlers should be located in `handlers` by default, this can be changed using `$app->config('handlers','path/to/handlers');`

### helpers

```php
$app->register('helper', '\zf\Helper');

$app->helper->register(require 'helpers.php');
$app->helper->register(require 'more-helpers.php');
```

`helpers.php`
```php
$exports = [];
$exports['item'] = function(){ ... };
$exports['element'] = function(){ ... };
return $exports;
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
