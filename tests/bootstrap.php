<?php

require __DIR__.'/../vendor/autoload.php';

\zf\FancyObject::setValidators(require __DIR__.'/../zf/validators.php');
\zf\FancyObject::setMappers(require __DIR__.'/../zf/mappers.php');
