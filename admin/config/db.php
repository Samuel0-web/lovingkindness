<?php

$host = 'localhost';
$db = 'lovingkindness';
$user = 'root';
$pass = '';

date_default_timezone_set('Africa/Lagos');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 🔥 THIS FIXES YOUR ISSUE
    $pdo->exec("SET time_zone = '+01:00'");

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}