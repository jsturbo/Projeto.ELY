<?php

use Illuminate\Http\Request;

// Suprimir avisos de deprecated do PHP 8.5
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

define('LARAVEL_START', microtime(true));

// Check if the application is under maintenance
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
