<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => false, // ⚠️ change to true on HTTPS
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true
]);

require __DIR__ . '/config/security.php';
require __DIR__ . '/config/functions.php';
require __DIR__ . '/config/db.php';

// get route
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = dirname($_SERVER['SCRIPT_NAME']);
$base = str_replace('\\', '/', $base);
$uri = preg_replace('#^' . preg_quote($base, '#') . '#', '', $uri);
$uri = trim($uri, '/');

// --------------------
// API ROUTING (FIRST)
// --------------------
if (str_starts_with($uri, 'api/')) {
    $apiPath = substr($uri, 4); // remove "api/"

    if ($apiPath === '' || preg_match('#\.{2}|[^a-zA-Z0-9_/-]#', $apiPath)) {
        http_response_code(400);
        exit;
    }

    $baseDir = realpath(__DIR__);
    $candidate = $baseDir . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . $apiPath . '.php';
    $realApiFile = realpath($candidate);

    if ($realApiFile &&
        str_starts_with($realApiFile, $baseDir . DIRECTORY_SEPARATOR . 'api') &&
        is_file($realApiFile)
    ) {
        require $realApiFile;
        exit;
    }

    // API route not found
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'API endpoint not found']);
    exit;
}

// --------------------
// FRONTEND ROUTES
// --------------------
$routes = [
    '' => 'index.php',
    'dashboard' => 'index.php',
    'users' => 'users.php',
    'enrollments' => 'enrollment.php',
    'inbox' => 'messages.php',
    'settings' => 'settings.php',
    'backup' => 'backup.php',
    'profile' => 'profile.php',
    'login' => 'login.php',
    'logout' => 'logout.php',
    'notifications' => 'notifications.php',
    'activity' => 'activity-logs.php',
    'forgot-password' => 'forgot-pass.php',
    '404' => '404.php'
];

// ✅ Check if route exists
if (!array_key_exists($uri, $routes)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

// 🔐 THEN protect valid routes (except login, forgot-password, logout)
$publicRoutes = ['login', 'forgot-password', 'logout', '404'];
$CURRENT_ROUTE = $uri;

if (!in_array($CURRENT_ROUTE, $publicRoutes)) {
    require __DIR__ . '/middleware/auth.php';
}

// load route
require __DIR__ . '/' . $routes[$uri];
exit;