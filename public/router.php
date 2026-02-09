<?php

/**
 * Router for PHP built-in server (Symfony standard)
 * Redirects all requests to index.php so that Symfony handles routing.
 */

// Serve static files (images, CSS, JS) directly
if (php_sapi_name() === 'cli-server') {
    $uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
        return false;
    }
}

// Tell PHP/Symfony that the front controller is index.php
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . DIRECTORY_SEPARATOR . 'index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';

require __DIR__ . DIRECTORY_SEPARATOR . 'index.php';
