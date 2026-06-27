<?php
require_once '../config/db.php';
require_once '../services/ActivityLogService.php';
header('Content-Type: application/json');
http_response_code(200);
$offset = (int)($_GET['offset'] ?? 0);
$groups = ActivityLogService::getGroupedAuditLogs($pdo, 100, $offset);
$totalLogs = ActivityLogService::getTotalLogs($pdo);
ob_start();
include '../partials/activity-items.php';
$html = ob_get_clean();

echo json_encode([
    'html' => $html,
    'hasMore' => ($offset + 100) < $totalLogs
]);