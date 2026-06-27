<?php
// only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login");
    exit;
}

// unset all session variables
$_SESSION = [];

session_regenerate_id(true);
// destroy session
session_destroy();

// delete session cookie (extra safety)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// redirect
header("Location: login");
exit;