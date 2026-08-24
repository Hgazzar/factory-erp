<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| PHP built-in server: serve real public files as static assets
|--------------------------------------------------------------------------
|
| On Railway we often run: php -S 0.0.0.0:$PORT -t public public/index.php
| When this file is the router, PHP sets SCRIPT_NAME to the asset path for
| existing files. Laravel then treats the path as "/" and redirects guests
| to /login — so /build/manifest.json and CSS never load.
|
| Returning false tells the built-in server to emit the file as-is.
*/
if (PHP_SAPI === 'cli-server') {
    $uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    if ($uri !== '/' && $uri !== '' && is_file(__DIR__.$uri)) {
        return false;
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
