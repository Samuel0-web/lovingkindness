<?php
$uri = $CURRENT_ROUTE ?? '';
$publicRoutes = ['login', 'forgot-password'];

if (!in_array($uri, $publicRoutes)) {

    if (!isset($_SESSION['admin_logged_in'])) {
        header("Location: login");
        exit;
    }

    // session hijack protection
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_unset();
        session_destroy();
        header("Location: login");
        exit;
    }
}

$timeout = 1800; // 30 mins

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: login");
    exit;
}

$_SESSION['last_activity'] = time();