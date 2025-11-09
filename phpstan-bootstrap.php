<?php

// Load Composer autoloader
require __DIR__.'/vendor/autoload.php';

// Create Laravel app
$app = new Illuminate\Foundation\Application(__DIR__);

// Define the Laravel version constant that Larastan expects
define('Larastan\Larastan\LARAVEL_VERSION', $app->version());
