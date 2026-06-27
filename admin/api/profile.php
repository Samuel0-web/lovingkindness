<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$userId = $_SESSION['admin_id'] ?? null;
if (!$userId) {
    echo json_encode(['ok' => false, 'e' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

if (!isset($_SESSION['pwd_attempts'])) {
    $_SESSION['pwd_attempts'] = 0;
    $_SESSION['pwd_last_attempt'] = time();
}

if ($_SESSION['pwd_attempts'] >= 5 && time() - $_SESSION['pwd_last_attempt'] < 300) {
    respond(false, 'Too many attempts. Try again later.');
}

$action = $_GET['action'] ?? '';
$allowedActions = ['update', 'password', 'avatar', 'remove_avatar'];

if (!in_array($action, $allowedActions)) {
    respond(false, 'Invalid action');
}

/* ================= HELPER ================= */
function respond($ok, $msg = null) {
    echo json_encode($ok ? ['ok' => true] : ['ok' => false, 'e' => $msg]);
    exit;
}

/* ================= UPDATE NAME / EMAIL ================= */
if ($action === 'update') {
    $payload = json_decode($_POST['p'] ?? '{}', true);
    $name  = trim($payload['n'] ?? '');
    $email = trim($payload['e'] ?? '');

    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(false, 'All fields required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(false, 'Invalid email');
    }

    // check if email already exists (excluding current user)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) {
        respond(false, 'Email already in use');
    }

    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $stmt->execute([$name, $email, $userId]);

    respond(true);
}

/* ================= CHANGE PASSWORD ================= */
if ($action === 'password') {
    $payload = json_decode($_POST['p'] ?? '{}', true);
    $old = $payload['old'] ?? '';
    $new = $payload['new'] ?? '';

    if (!$old || !$new) {
        respond(false, 'Missing fields');
    }

    if (strlen($new) < 8 || !preg_match('/[A-Z]/', $new) || !preg_match('/[a-z]/', $new) ||
        !preg_match('/[0-9]/', $new)) {
        respond(false, 'Password must include upper, lower, number and be 8+ chars');
    }

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($old, $user['password'])) {
        respond(false, 'Incorrect current password');
    }

    if (!$user || !password_verify($old, $user['password'])) {
        $_SESSION['pwd_attempts']++;
        $_SESSION['pwd_last_attempt'] = time();
        respond(false, 'Incorrect current password');
    }

    $_SESSION['pwd_attempts'] = 0;
    $hashed = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashed, $userId]);
    respond(true);
}

/* ================= AVATAR UPLOAD ================= */
if ($action === 'avatar') {
    if (!isset($_FILES['avatar'])) {
        respond(false, 'No file uploaded');
    }

    $file = $_FILES['avatar'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        respond(false, 'Upload failed');
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        respond(false, 'Max file size is 2MB');
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $mime = mime_content_type($file['tmp_name']);

    if (!in_array($mime, $allowed)) {
        respond(false, 'Invalid image type');
    }

    $ext = match ($mime) {
        'image/jpeg' => '.jpg',
        'image/png'  => '.png',
        'image/webp' => '.webp',
        default => ''
    };

    $uploadDir = __DIR__ . '/../uploads/avatars/';

    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        respond(false, 'Invalid image file');
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = bin2hex(random_bytes(16)) . $ext;
    $path = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        respond(false, 'Failed to save image');
    }

    $url = 'uploads/avatars/' . $filename;

    $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $stmt->execute([$url, $userId]);

    echo json_encode([
        'ok' => true,
        'url' => $url
    ]);
    exit;
}

/* ================= REMOVE AVATAR ================= */
if ($action === 'remove_avatar') {

    // get current avatar
    $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !$user['profile_picture']) {
        respond(false, 'No avatar to remove');
    }

    $filePath = __DIR__ . '/../' . $user['profile_picture'];

    // delete file if exists
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // remove from DB
    $stmt = $pdo->prepare("UPDATE users SET profile_picture = NULL WHERE id = ?");
    $stmt->execute([$userId]);
    respond(true);
}

/* ================= DEFAULT ================= */
respond(false, 'Invalid action');