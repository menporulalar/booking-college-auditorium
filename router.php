<?php
// router.php for PHP built-in server
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . '/public' . $uri;

if ($uri !== '/' && file_exists($file) && is_file($file)) {
    return false;
}

require __DIR__ . '/public/index.php';
