<?php
session_start();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ✅ auto-detect base folder (THIS is the magic)
$base = dirname($_SERVER['SCRIPT_NAME']); 
$base = str_replace('\\', '/', $base);

// remove base from URI
$uri = preg_replace('#^' . preg_quote($base, '#') . '#', '', $uri);

$uri = trim($uri, '/');

// routes
$routes = [
    '' => 'index.php',
    '/' => 'index.php',
    'home' => 'index.php',

    'about' => 'about.php',
    'tutoring' => 'tutoring.php',
    'training' => 'training.php',
    'contact' => 'contact.php',
    'enroll' => 'enroll.php',
    'success' => 'success.php',

    '404' => '404.php'
];

// match route
if (array_key_exists($uri, $routes)) {
    require __DIR__ . '/' . $routes[$uri];
    exit;
}

// 404
http_response_code(404);
require __DIR__ . '/404.php';