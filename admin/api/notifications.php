<?php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../../services/NotificationService.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'e' => 'Unauthorized'
    ]);
    exit;
}

$currentRole = $_SESSION['role'] ?? null;

if (!in_array($currentRole, ['owner', 'admin'], true)) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'e' => 'Forbidden'
    ]);
    exit;
}

$userId = (int) $_SESSION['admin_id'];

function jsonInput(): array {
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

function requirePost(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'ok' => false,
            'e' => 'Method not allowed'
        ]);
        exit;
    }
}

$action = $_GET['action'] ?? '';

try {
    if ($action === 'unseenCount') {
        echo json_encode([
            'ok' => true,
            'count' => NotificationService::getUnseenCount($pdo, $userId)
        ]);
        exit;
    }

    if ($action === 'markSeen') {
        requirePost();
        verify_csrf();

        NotificationService::markSeen($pdo, $userId);

        echo json_encode([
            'ok' => true
        ]);
        exit;
    }

    if ($action === 'recent') {
        $limit = isset($_GET['limit']) ? max(1, min(50, (int) $_GET['limit'])) : 20;
        echo json_encode([
            'ok' => true,
            'notifications' => NotificationService::getRecent($pdo, $userId, $limit)
        ]);
        exit;
    }

    if ($action === 'list') {
        echo json_encode([
            'ok' => true,
            'notifications' => NotificationService::getAll($pdo, $userId)
        ]);
        exit;
    }

    if ($action === 'markRead') {
        requirePost();
        verify_csrf();

        $data = jsonInput();
        $notificationId = (int) ($data['id'] ?? 0);

        if ($notificationId <= 0) {
            throw new Exception('Invalid notification');
        }

        NotificationService::markRead($pdo, $notificationId, $userId);

        echo json_encode([
            'ok' => true
        ]);
        exit;
    }

    if ($action === 'markUnread') {
        requirePost();
        verify_csrf();

        $data = jsonInput();
        NotificationService::markUnread($pdo, (int)$data['id'], $userId);

        echo json_encode([
            'ok' => true
        ]);
        exit;
    }

    if ($action === 'markAllRead') {
        requirePost();
        verify_csrf();

        NotificationService::markAllRead($pdo, $userId);

        echo json_encode([
            'ok' => true
        ]);
        exit;
    }

    if ($action === 'delete') {
        requirePost();
        verify_csrf();

        $data = jsonInput();
        NotificationService::delete($pdo, (int)$data['id'], $userId);

        echo json_encode([
            'ok' => true
        ]);
        exit;
    }

    if ($action === 'deleteAll') {
        requirePost();
        verify_csrf();

        NotificationService::deleteAll($pdo, $userId);

        echo json_encode([
            'ok' => true
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'e' => 'Invalid action'
    ]);
    exit;
} catch (Exception $e) {
    http_response_code(400);
    error_log(sprintf(
        '[Notifications API] %s in %s:%d',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    echo json_encode([
        'ok' => false,
        'e' => 'Something went wrong'
    ]);
}