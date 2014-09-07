# zf [![Build Status](https://travis-ci.org/zweifisch/zf.png?branch=master)](https://travis-ci.org/zweifisch/zf)

a micro php framework for both web and cli

*requires php 5.6*

## a taste of zf

```php
<?php

require 'vendor/autoload.php';

$app = new zf\App;

/**
 * @param string $name your name
 * @param string $more an optional param
 */
$app->get('/hello/:name', function($name, $more='') {
	return ['hello' => $name.$more];
});

$app->get('/', function() {
	return $this->render('landing-page');
});

$app->resource('post', 'user');

$app->run();
```

```
$ php -S localhost:8000/index.php &> /tmp/server.log
$ curl localhost:8000/hello/foo?more=bar
{"hello": "foobar"}
```

### cli

```php
<?php

require 'vendor/autoload.php';

$app = new zf\App;

/**
 * @param string $name your name
 * @param int $times times to repeat
 */
$app->cmd('hello <name>', function($name, $times=1) {
	return str_repeat("hello $name\n", $times);
});

/**
 * @param bool $showDate also print date
 */
$app->cmd('time', function($showDate=false) {
	return date($showDate ? 'Y-m-d H:i:s' : 'H:i:s');
});

$app->run();
```

```sh
$ php cli.php
Usage:

  php cli.php hello <name>
      name   	your name      
      --times	times to repeat

  php cli.php time
      --show-date	also print date
```

```sh
$ php cli.php hello --times=2 zf
hello zf
hello zf
```

work in progress [documentation](http://zweifisch.github.io/zf-doc/getting_started.html)

## tests

to run tests

```sh
composer.phar install --dev
vendor/bin/phpunit -c unit-test
```
