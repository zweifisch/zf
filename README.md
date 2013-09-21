# zf [![Build Status](https://travis-ci.org/zweifisch/zf.png?branch=master)](https://travis-ci.org/zweifisch/zf)

a micro php framework for both web and cli

* closure for everything
* lazy, scalable, extendable
* commandline routing, distribute as phar
* jsonrpc
* validation using jsonschema
* events
* jsonp
* ideal for building restful apis or commandline apps
* requires php 5.4

```php
<?php

require 'vendor/autoload.php';

$app = new zf\App();

$app->get('/hello/:name', function($name, $more=''){
	return ['hello' => $name.$more];
});

$app->run();
```

work in progress [documentation](http://zweifisch.github.io/zf-doc/getting_started.html)

## tests

to run tests

```sh
composer.phar install --dev
vendor/bin/phpunit -c unit-test
```
