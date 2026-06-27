<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/ActivityLogService.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => 0, 'e' => 'Unauthourized']);
    exit;
}

if ($_SESSION['role'] !== 'owner') {
    http_response_code(403);
    exit;
}

$_api = $_GET['_api'] ?? $_POST['_api'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => 0, 'e' => 'method']);
    exit;
}

// 🔐 ADD THIS
verify_csrf();

// decode payload
$d = json_decode($_POST['p'] ?? '', true) ?: [];

// common fields
$id    = (int)($d['i'] ?? 0);
$name  = trim($d['n'] ?? '');
$email = trim($d['e'] ?? '');
$pass  = $d['p'] ?? '';

function isStrongPassword($password) {
    return strlen($password) >= 8 &&
        preg_match('/[A-Z]/', $password) &&
        preg_match('/[a-z]/', $password) &&
        preg_match('/[0-9]/', $password) &&
        preg_match('/[\W]/', $password);
}

function isCommonPassword($password) {
    $common = [
        '123456789','password','12345678','qwerty','abc123',
        '11111111','123123123','admin','letmein','welcome'
    ];
    return in_array(strtolower($password), $common);
}

try {

    // ================= CREATE =================
    if ($_api === 'create') {
        $email = strtolower(trim($d['e'] ?? ''));
        if ($name === '' || $email === '') {
            echo json_encode(['ok' => 0, 'e' => 'Missing']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['ok' => 0, 'e' => 'Invalid email']);
            exit;
        }

        if (!$pass || !isStrongPassword($pass)) {
            echo json_encode([
                'ok' => 0,
                'e' => 'Password must be at least 8 chars and include uppercase, lowercase, number, and symbol'
            ]);
            exit;
        }

        if (isCommonPassword($pass)) {
            echo json_encode([
                'ok' => 0,
                'e' => 'This password is too common. Choose a stronger one.'
            ]);
            exit;
        }

        if (!isset($_SESSION['create_attempts'])) {
            $_SESSION['create_attempts'] = 0;
            $_SESSION['create_time'] = time();
        }

        if ($_SESSION['create_attempts'] >= 5 && (time() - $_SESSION['create_time']) < 60) {
            echo json_encode([
                'ok' => 0,
                'e' => 'Too many requests. Try again later.'
            ]);
            exit;
        }

        $_SESSION['create_attempts']++;

        if ((time() - $_SESSION['create_time']) > 60) {
            $_SESSION['create_attempts'] = 0;
            $_SESSION['create_time'] = time();
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['ok' => 0, 'e' => 'Email already exists']);
            exit;
        }

        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(" INSERT INTO users (name, email, password, last_login, created_at)
            VALUES (?, ?, ?, NULL, NOW())
        ");

        $stmt->execute([$name, $email, $hash]);
        $id = $pdo->lastInsertId();

        ActivityLogService::log($pdo, $_SESSION['admin_id'], $_SESSION['admin_name'],
            'create', "Created user {$name} ({$email})", 'user', (int)$id
        );
    }

    // ================= UPDATE =================
    elseif ($_api === 'update') {
        $email = strtolower(trim($d['e'] ?? ''));

        if (!$id || $name === '' || $email === '') {
            echo json_encode(['ok' => 0, 'e' => 'Missing']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['ok' => 0, 'e' => 'Invalid email']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $oldUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pass) {
            if (!isStrongPassword($pass)) {
                echo json_encode([
                    'ok' => 0,
                    'e' => 'Password must be at least 8 chars and include uppercase, lowercase, number, and symbol'
                ]);
                exit;
            }

            if (isCommonPassword($pass)) {
                echo json_encode([
                    'ok' => 0,
                    'e' => 'This password is too common. Choose a stronger one.'
                ]);
                exit;
            }
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, password=? WHERE id=?");
            $stmt->execute([$name, $email, $hash, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=? WHERE id=?");
            $stmt->execute([$name, $email, $id]);
        }

        ActivityLogService::log($pdo, $_SESSION['admin_id'], $_SESSION['admin_name'],
            'update', "Updated user {$oldUser['name']} ({$oldUser['email']}) → {$name} ({$email})",
            'user', $id
        );
    }

    // ================= DELETE =================
    elseif ($_api === 'delete') {

        if (!$id) {
            echo json_encode(['ok' => 0, 'e' => 'missing']);
            exit;
        }

        $currentUserId = $_SESSION['admin_id'];

        if ($id == $currentUserId) {
            echo json_encode(['ok' => 0, 'e' => 'Cannot delete yourself']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT role FROM users WHERE id=?");
        $stmt->execute([$id]);
        $target = $stmt->fetch();

        if ($target && $target['role'] === 'owner') {
            echo json_encode(['ok' => 0, 'e' => 'Cannot delete owner']);
            exit;
        }

        if ($_SESSION['role'] !== 'owner' && $target['role'] === 'admin') {
            echo json_encode(['ok' => 0, 'e' => 'Only owner can delete admins']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['ok' => 0, 'e' => 'User not found']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        ActivityLogService::log($pdo, $_SESSION['admin_id'], $_SESSION['admin_name'],
            'delete', "Deleted user {$user['name']} ({$user['email']})", 'user', $user['id']
        );

        if ($stmt->rowCount() === 0) {
            echo json_encode(['ok' => 0, 'e' => 'User not found']);
            exit;
        }

        echo json_encode([
            'ok' => 1,
            'm' => ['id' => [$id]],
            't' => time()
        ]);
        exit;
    }

    // ================= BULK DELETE =================
    elseif ($_api === 'bulk_delete') {
        $ids = $d['ids'] ?? [];

        if (!is_array($ids) || empty($ids)) {
            echo json_encode(['ok' => 0, 'e' => 'missing']);
            exit;
        }

        $currentUserId = $_SESSION['admin_id'];

        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, fn($uid) => $uid != $currentUserId);

        if (empty($ids)) {
            echo json_encode(['ok' => 0, 'e' => 'invalid']);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // Before delete, fetch allowed IDs
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id IN ($placeholders)
            AND id != ? AND role != 'owner'
        ");

        $stmt->execute([...$ids, $currentUserId]);
        $allowedIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($allowedIds)) {
            echo json_encode(['ok' => 0, 'e' => 'Nothing to delete']);
            exit;
        }

        $stmt->execute([...$ids, $currentUserId]);
        $usersToDelete = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $allowedIds = array_column($usersToDelete, 'id');
        $placeholders2 = implode(',', array_fill(0, count($allowedIds), '?'));
        $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders2)");
        $stmt->execute($allowedIds);

        foreach ($usersToDelete as $user) {
            ActivityLogService::log($pdo, $_SESSION['admin_id'], $_SESSION['admin_name'],
                'delete', "Bulk deleted user {$user['name']} ({$user['email']})", 'user',
                $user['id']
            );
        }

        echo json_encode([
            'ok' => 1,
            'm' => ['ids' => $allowedIds],
        ]);
        exit;
    }

    else {
        echo json_encode(['ok' => 0, 'e' => 'action']);
        exit;
    }

    // fetch user (for create/update)
    $stmt = $pdo->prepare("SELECT id, name, email, profile_picture, last_login, created_at, role
        FROM users WHERE id = ?
    ");
    $stmt->execute([$id]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => 1,
        'm' => [
            'u' => [
                'i'  => (int)$u['id'],
                'n'  => $u['name'],
                'e'  => $u['email'],
                'p'  => $u['profile_picture'],
                'la' => $u['last_login'],
                'c'  => $u['created_at'],
                'r'  => $u['role']
            ]
        ],
        't' => time()
    ]);
    exit;

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(['ok' => 0, 'e' => 'Email already exists']); 
    } else {
        echo json_encode(['ok' => 0, 'e' => 'db']);
    }
}