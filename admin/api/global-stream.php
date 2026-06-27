<?php
require __DIR__ . '/../config/db.php';

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

$lastMessageCount = -1;
$lastEnrollmentCount = -1;
$lastNotificationCount = -1;

$notificationStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?
    AND is_seen = 0
");

while (true) {
    if (connection_aborted()) { break; }

    // Messages
    $messageCount = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'
        AND deleted_at IS NULL
    ")->fetchColumn();

    if ($messageCount !== $lastMessageCount) {
        echo "event: unread_messages\n";

        echo "data: " . json_encode([
            'count' => $messageCount
        ]) . "\n\n";

        $lastMessageCount = $messageCount;
    }

    // Enrollments
    $enrollmentCount = (int)$pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'pending'
    ")->fetchColumn();

    if ($enrollmentCount !== $lastEnrollmentCount) {
        echo "event: pending_enrollments\n";
        echo "data: " . json_encode([
            'count' => $enrollmentCount
        ]) . "\n\n";

        $lastEnrollmentCount = $enrollmentCount;
    }

    $notificationStmt->execute([$userId]);
    $notificationCount = (int) $notificationStmt->fetchColumn();

    if ($notificationCount !== $lastNotificationCount) {
        echo "event: unread_notifications\n";
        echo "data: " . json_encode([
            'count' => $notificationCount
        ]) . "\n\n";

        $lastNotificationCount = $notificationCount;
    }

    echo ": heartbeat\n\n";

    if (ob_get_level() > 0) {
        ob_flush();
    }

    flush();
    sleep(2);
}