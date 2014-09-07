<?php

namespace helper;

function get($app, $path, $params=[]) {
    $app->request->path = $path;
    $app->request->method = 'GET';
    $_GET = $params;
    return $app;
}

function post($app, $path, $body) {
    $app->request->path = $path;
    $app->body = $body;
    return $app->run();
}

