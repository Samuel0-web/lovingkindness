<?php

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    exit;
}

session_write_close();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

set_time_limit(0);
ignore_user_abort(false);

error_log('SSE started');

while (ob_get_level()) {
    ob_end_flush();
}

ob_implicit_flush(true);

$lastId = (int)($_GET['last_id'] ?? 0);

while (true) {
    if (connection_aborted()) {
        error_log('SSE ended');
        break;
    }

    $stmt = $pdo->prepare("SELECT id, full_name, inquiry_type, status, message, created_at
        FROM contact_messages WHERE id > ? AND deleted_at IS NULL ORDER BY id ASC
    ");

    $stmt->execute([$lastId]);

    while ($message = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "event: new_message\n";
        echo "data: " . json_encode($message) . "\n\n";
        $lastId = $message['id'];

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }

    echo ": heartbeat\n\n";

    if (ob_get_level() > 0) {
        ob_flush();
    }

    flush();
    sleep(2);
}