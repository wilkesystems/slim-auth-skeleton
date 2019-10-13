<?php
use App\Bootstrap;

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

if (! file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die('please run "composer install"');
}

require __DIR__ . '/../vendor/autoload.php';

if (! class_exists('App\Bootstrap')) {
    die('Bootstrap class not found');
}

$app = new Bootstrap();

$app->run();