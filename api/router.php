<?php

header('Content-Type: application/json');

$route = $_GET['route'] ?? '';
$route = trim($route, '/');

$routes = [
    'submit-form' => 'submit_form.php',
    'contact-submit' => 'contact_form.php'
];

if (array_key_exists($route, $routes)) {
    require __DIR__ . '/' . $routes[$route];
    exit;
}

http_response_code(404);

echo json_encode([
    'success' => false,
    'message' => 'API endpoint not found'
]);