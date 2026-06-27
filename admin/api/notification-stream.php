<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../../services/NotificationService.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    exit;
}

$userId = (int) $_SESSION['admin_id'];

session_write_close();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

set_time_limit(0);
ignore_user_abort(false);

while (ob_get_level()) {
    ob_end_flush();
}

ob_implicit_flush(true);

$lastId = (int)($_GET['last_id'] ?? 0);

while (true) {

    if (connection_aborted()) {
        break;
    }

    $notifications = NotificationService::getSince($pdo, $userId, $lastId);

    foreach ($notifications as $notification) {
        $lastId = max($lastId, (int)$notification['id']);
        echo "event: notification\n";
        echo "data: " . json_encode($notification) . "\n\n";
    }

    echo ": heartbeat\n\n";

    if (ob_get_level() > 0) {
        ob_flush();
    }

    flush();
    sleep(2);
}