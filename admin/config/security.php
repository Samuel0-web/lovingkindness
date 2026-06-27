<?php

// secure headers
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self' https:; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline' https:;");

// generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// helper: get token
function csrf_token(): string {
    return $_SESSION['csrf_token'] ?? '';
}

// helper: verify token
function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (empty($_SESSION['csrf_token']) || empty($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);

        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
            header('Content-Type: application/json');

            echo json_encode([
                'ok' => false,
                'e' => 'Invalid CSRF token'
            ]);
        } else {
            exit('Invalid CSRF token');
        }

        exit;
    }
}

// init login attempts safely
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}